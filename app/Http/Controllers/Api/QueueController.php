<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Traits\ApiResponse;
use App\Http\Requests\StoreQueueRequest;
use App\Http\Requests\UpdateQueueRequest;
use App\Http\Resources\QueueResource;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    use ApiResponse;

    public function index(Request $request) {
        $user = $request->user();
        $query = Queue::with(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);

        if ($user->role === 'patient') {
            $query->whereHas('patient', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->role === 'doctor') {
            $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
            if ($doctor) {
                $query->where('doctor_id', $doctor->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->has('page') || $request->has('per_page') || $request->has('paginate')) {
            $limit = $request->input('per_page', 20);
            return $this->successResponse(QueueResource::collection($query->paginate($limit)), 'Daftar antrian berhasil diambil');
        }

        $queues = $query->get();
        return $this->successResponse(QueueResource::collection($queues), 'Daftar antrian berhasil diambil');
    }

    public function store(StoreQueueRequest $request) {
        $user = $request->user();

        // Mencegah Celah IDOR: Pasien tidak bisa mendaftarkan antrean atas nama patient_id orang lain
        if ($user->role === 'patient') {
            $patient = \App\Models\Patient::where('user_id', $user->id)->first();
            if (!$patient || $patient->id != $request->patient_id) {
                return $this->errorResponse('Akses ditolak. Anda tidak dapat mendaftarkan pasien lain.', 403);
            }
        }

        // 6. Validasi Jarak Tanggal (H-7 s.d. H0) - Independent of concurrent database state
        $bookingDate = \Carbon\Carbon::parse($request->date)->startOfDay();
        $today = \Carbon\Carbon::today()->startOfDay();
        $daysDiff = $today->diffInDays($bookingDate, false);

        if ($daysDiff < 0 || $daysDiff > 7) {
            return $this->errorResponse('Pendaftaran online hanya diperbolehkan H-7 sampai hari ini sebelum tanggal kunjungan', 422);
        }

        try {
            return DB::transaction(function () use ($request, $bookingDate) {
                // Perbaikan Deadlock: Urutan lock harus konsisten di semua request, selalu Patient lalu Polyclinic
                $patient = \App\Models\Patient::where('id', $request->patient_id)->lockForUpdate()->first();
                if (!$patient) {
                    return $this->errorResponse('Pasien tidak ditemukan.', 404);
                }

                // Auto-detect prioritas lansia berdasarkan tanggal lahir pasien (server-side, anti manipulasi)
                $isPriority = false;
                $patientUser = $patient->user;
                if ($patientUser && $patientUser->birth_date) {
                    $age = \Carbon\Carbon::parse($patientUser->birth_date)->age;
                    $isPriority = $age >= 60;
                }

                $polyclinic = \App\Models\Polyclinic::where('id', $request->polyclinic_id)->lockForUpdate()->first();
                if (!$polyclinic) {
                    return $this->errorResponse('Poliklinik tidak ditemukan.', 404);
                }

                // 1. Validasi Tanggal Libur Puskesmas
                $isHoliday = \App\Models\ClinicHoliday::where('holiday_date', $request->date)->exists();
                if ($isHoliday) {
                    return $this->errorResponse('Pendaftaran gagal. Puskesmas libur operasional pada tanggal tersebut.', 422);
                }

                // 2. Validasi Tanggal Cuti Dokter
                $isLeave = \App\Models\DoctorLeave::where('doctor_id', $request->doctor_id)
                                                  ->where('leave_date', $request->date)
                                                  ->exists();
                if ($isLeave) {
                    return $this->errorResponse('Pendaftaran gagal. Dokter yang bersangkutan sedang cuti pada tanggal tersebut.', 422);
                }

                // 3. Validasi Kontrak Hubungan Dokter - Poliklinik - Jadwal Praktik (Perbaikan SQL Mismatch dengan query relasi)
                $schedule = \App\Models\DoctorSchedule::where('id', $request->doctor_schedule_id)
                                                      ->where('doctor_id', $request->doctor_id)
                                                      ->whereHas('doctor', function($q) use ($request) {
                                                          $q->where('polyclinic_id', $request->polyclinic_id);
                                                      })
                                                      ->first();
                if (!$schedule) {
                    return $this->errorResponse('Jadwal dokter tidak cocok dengan poliklinik atau dokter yang dipilih.', 422);
                }

                // 4. Validasi Antrean Aktif di Poliklinik yang Sama (Dilakukan di bawah Lock)
                $existingQueueSamePoly = Queue::where('patient_id', $request->patient_id)
                                              ->where('polyclinic_id', $request->polyclinic_id)
                                              ->where('date', $request->date)
                                              ->whereNotIn('status', ['cancelled'])
                                              ->lockForUpdate()
                                              ->first();

                if ($existingQueueSamePoly) {
                    return $this->errorResponse('Anda sudah memiliki antrean aktif di poliklinik ini untuk tanggal tersebut', 422);
                }

                // 5. Validasi Tabrakan Jam Praktik Lintas Poliklinik di Hari yang Sama (Dilakukan di bawah Lock sebelum insert)
                $activeQueuesSameDate = Queue::where('patient_id', $request->patient_id)
                                             ->where('date', $request->date)
                                             ->whereNotIn('status', ['cancelled'])
                                             ->lockForUpdate()
                                             ->get();

                $startNew = $schedule->start_time;
                $endNew = $schedule->end_time;

                foreach ($activeQueuesSameDate as $existingQueue) {
                    $existingSchedule = \App\Models\DoctorSchedule::find($existingQueue->doctor_schedule_id);
                    if ($existingSchedule) {
                        $startExist = $existingSchedule->start_time;
                        $endExist = $existingSchedule->end_time;

                        // Cek tumpang tindih waktu (Overlap formula: startA < endB && startB < endA)
                        if ($startNew < $endExist && $startExist < $endNew) {
                            return $this->errorResponse('Pendaftaran gagal. Jam pelayanan dokter bentrok dengan antrean aktif Anda yang lain pada hari tersebut (' . substr($startExist, 0, 5) . ' - ' . substr($endExist, 0, 5) . ' WIB).', 422);
                        }
                    }
                }

                $days = [
                    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                ];
                $dayName = $days[$bookingDate->format('l')];

                // Pastikan nama hari cocok dengan jadwal
                if ($schedule->day_of_week !== $dayName) {
                    return $this->errorResponse('Hari terpilih tidak cocok dengan hari praktik jadwal dokter tersebut.', 422);
                }

                if ($bookingDate->isSameDay(\Carbon\Carbon::now())) {
                    $serviceStartTime = \Carbon\Carbon::parse($request->date . ' ' . $schedule->start_time);
                    if (\Carbon\Carbon::now()->greaterThanOrEqualTo($serviceStartTime)) {
                        return $this->errorResponse('Pendaftaran untuk hari ini hanya bisa dilakukan sebelum jam mulai praktik dokter.', 422);
                    }
                }

                // Kalkulasi kuota dinamis berdasarkan jam kerja dokter (durasi_menit / 15)
                $startTime = \Carbon\Carbon::parse($schedule->start_time);
                $endTime = \Carbon\Carbon::parse($schedule->end_time);
                $duration = $startTime->diffInMinutes($endTime);
                $quota = $duration > 0 ? floor($duration / 15) : 10;

                // Hitung total antrean aktif dokter untuk jadwal ini pada tanggal terpilih
                $activeBookingsCount = Queue::where('doctor_schedule_id', $request->doctor_schedule_id)
                                             ->where('date', $request->date)
                                             ->whereNotIn('status', ['cancelled'])
                                             ->lockForUpdate()
                                             ->count();

                if ($activeBookingsCount >= $quota) {
                    return $this->errorResponse('Kuota pendaftaran untuk jadwal dokter tersebut sudah penuh (Kapasitas: ' . $quota . ' pasien)', 422);
                }

                $prefix = strtoupper($polyclinic->code);

                $lastQueue = Queue::withTrashed()
                                   ->whereDate('date', $request->date)
                                   ->where('polyclinic_id', $polyclinic->id)
                                   ->orderBy('id', 'desc')
                                   ->first();
                $maxNumber = 0;
                if ($lastQueue && preg_match('/(\d+)$/', $lastQueue->queue_number, $m)) {
                    $maxNumber = (int)$m[1];
                }

                $nextNumber = ($maxNumber ?? 0) + 1;

                $queueNumber = sprintf('%s-%03d', $prefix, $nextNumber);

                $data = $request->validated();
                $data['queue_number'] = $queueNumber;
                $data['status'] = 'booked';
                $data['is_priority'] = $isPriority;

                $queue = Queue::storeData($data);
                $est = Queue::calculateEstimatedServiceTime($queue);
                if ($est) {
                    $queue->update(['estimated_service_time' => $est]);
                }
                Queue::recalculateEstimatedTimes($queue->polyclinic_id, $queue->date);

                $queue->load(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);
                return $this->successResponse(new QueueResource($queue), 'Antrian berhasil dibuat', 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan sistem saat mengambil nomor antrean: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id) {
        try {
            $queue = Queue::getById($id);
            $user = $request->user();

            if ($user->role === 'patient' && ($queue->patient?->user_id ?? null) !== $user->id) {
                return $this->errorResponse('Akses ditolak. Anda tidak dapat melihat detail antrean orang lain.', 403);
            }

            return $this->successResponse(new QueueResource($queue), 'Detail antrian ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data antrian tidak ditemukan', 404);
        }
    }

    public function update(UpdateQueueRequest $request, $id) {
        try {
            $user = $request->user();
            if ($user->role === 'patient') {
                return $this->errorResponse('Akses ditolak. Pasien tidak diizinkan mengubah status antrean.', 403);
            }

            $queueToUpdate = Queue::findOrFail($id);

            if ($user->role === 'doctor') {
                $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
                if (!$doctor || $queueToUpdate->doctor_id !== $doctor->id) {
                    return $this->errorResponse('Akses ditolak. Dokter hanya dapat mengubah status antrean miliknya sendiri.', 403);
                }
            }

            $newStatus = $request->status;
            $timeError = $this->validateServiceTimeWindow($queueToUpdate, $user, $newStatus);
            if ($timeError) {
                return $this->errorResponse($timeError, 422);
            }

            $oldStatus = $queueToUpdate->status;

            if ($newStatus && $oldStatus !== $newStatus) {
                $validTransitions = [
                    'booked' => ['waiting', 'cancelled'],
                    'waiting' => ['examining', 'cancelled'],
                    'examining' => ['completed'],
                    'completed' => [],
                    'cancelled' => []
                ];

                if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
                    return $this->errorResponse("Transisi status tidak valid dari '$oldStatus' ke '$newStatus'.", 422);
                }

                if ($newStatus === 'cancelled') {
                    if ($user->role === 'doctor') {
                        return $this->errorResponse('Akses ditolak. Dokter tidak diizinkan membatalkan antrean.', 403);
                    }

                    // Logika pembatalan oleh pasien telah dipindahkan seluruhnya ke method destroy() (DELETE).
                    // Hal ini mencegah dead code karena pasien memang tidak diizinkan mengakses endpoint PUT/PATCH ini.

                    if (!in_array($queueToUpdate->status, ['booked', 'waiting'])) {
                        return $this->errorResponse('Hanya antrean dengan status dipesan atau menunggu yang dapat dibatalkan.', 422);
                    }
                }
            }

            $validatedData = $request->validated();
            if (isset($validatedData['status']) && $validatedData['status'] === 'examining') {
                $existingExamining = Queue::where('polyclinic_id', $queueToUpdate->polyclinic_id)
                    ->where('doctor_id', $queueToUpdate->doctor_id)
                    ->where('date', $queueToUpdate->date)
                    ->where('status', 'examining')
                    ->where('id', '!=', $id)
                    ->exists();

                if ($existingExamining) {
                    return $this->errorResponse('Tidak dapat memanggil pasien. Masih ada pasien yang sedang diperiksa di poliklinik ini.', 422);
                }

                $doctor = \App\Models\Doctor::find($queueToUpdate->doctor_id);
                if ($doctor && !$doctor->is_online) {
                    return $this->errorResponse('Tidak dapat memanggil pasien. Dokter yang bersangkutan sedang beristirahat/offline.', 422);
                }

                $validatedData['called_time'] = now();
            }

            $data = Queue::updateData($id, $validatedData);
            Queue::recalculateEstimatedTimes($queueToUpdate->polyclinic_id, $queueToUpdate->date);

            if (isset($validatedData['status']) && $validatedData['status'] === 'examining') {
                $updatedQueue = Queue::with('patient.user')->find($id);
                $fcmToken = $updatedQueue->patient?->user?->fcm_token ?? null;

                if ($fcmToken) {
                    $firebaseService = new \App\Services\FirebaseNotificationService();
                    $title = "Giliran Anda!";
                    $body = "Silakan masuk ke ruangan dokter sekarang (Nomor Antrean: {$updatedQueue->queue_number}).";
                    $firebaseService->sendToToken($fcmToken, $title, $body, [
                        'queue_id' => $id,
                        'status' => 'examining'
                    ]);
                }
            }

            $queue = Queue::getById($id);
            return $this->successResponse(new QueueResource($queue), 'Status antrian berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data antrian tidak ditemukan', 404);
        }
    }

    public function destroy(Request $request, $id) {
        try {
            $queue = Queue::findOrFail($id);
            $user = $request->user();

            if ($user->role === 'doctor') {
                return $this->errorResponse('Akses ditolak. Dokter tidak diizinkan membatalkan antrean.', 403);
            }

            if ($user->role === 'patient') {
                if (($queue->patient?->user_id ?? null) !== $user->id) {
                    return $this->errorResponse('Akses ditolak. Anda hanya dapat membatalkan antrean Anda sendiri.', 403);
                }

                $schedule = $queue->doctorSchedule;
                if ($schedule) {
                    $serviceTime = \Carbon\Carbon::parse($queue->date . ' ' . $schedule->start_time);
                    $isRecentlyCreated = $queue->created_at && \Carbon\Carbon::parse($queue->created_at)->diffInMinutes(now()) <= 15;
                    
                    if ($isRecentlyCreated && now()->greaterThanOrEqualTo($serviceTime)) {
                        return $this->errorResponse('Pembatalan gagal. Waktu pelayanan dokter sudah dimulai/terlewat.', 422);
                    }
                    
                    if (!$isRecentlyCreated && now()->greaterThanOrEqualTo($serviceTime->subHours(2))) {
                        return $this->errorResponse('Pembatalan gagal. Antrean tidak dapat dibatalkan kurang dari 2 jam sebelum pelayanan dimulai.', 422);
                    }
                }
            }

            if (!in_array($queue->status, ['booked', 'waiting'])) {
                return $this->errorResponse('Hanya antrean dengan status dipesan atau menunggu yang dapat dibatalkan.', 422);
            }

            $queue->update(['status' => 'cancelled']);
            Queue::recalculateEstimatedTimes($queue->polyclinic_id, $queue->date);
            $queue->load(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);
            return $this->successResponse(new QueueResource($queue), 'Antrian berhasil dibatalkan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal membatalkan, data antrian tidak ditemukan', 404);
        }
    }

    public function restore($id) {
        try {
            Queue::restoreData($id);
            return $this->successResponse(null, 'Data antrian berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan di tempat sampah', 404);
        }
    }

    public function checkIn(Request $request, $id) {
        try {
            if ($request->user()->role !== 'admin') {
                return $this->errorResponse('Akses ditolak. Hanya petugas administrasi yang dapat memverifikasi Check-in.', 403);
            }

            $queue = Queue::findOrFail($id);

            if (!\Carbon\Carbon::parse($queue->date)->isToday()) {
                return $this->errorResponse('Check-in hanya dapat dilakukan pada tanggal pendaftaran (' . $queue->date . ')', 400);
            }

            $timeError = $this->validateServiceTimeWindow($queue, $request->user(), 'waiting');
            if ($timeError) {
                return $this->errorResponse($timeError, 422);
            }

            if ($queue->status !== 'booked') {
                return $this->errorResponse('Antrean sudah check-in atau tidak valid', 400);
            }

            $queue->update([
                'status' => 'waiting',
                'check_in_time' => now(),
                'reason' => $request->input('reason')
            ]);
            Queue::recalculateEstimatedTimes($queue->polyclinic_id, $queue->date);

            return $this->successResponse(new QueueResource($queue), 'Check-in berhasil via QR Scanner');
        } catch (Exception $e) {
            return $this->errorResponse('Data antrean tidak ditemukan', 404);
        }
    }

    public function recall(Request $request, $id) {
        try {
            if ($request->user()->role !== 'admin') {
                return $this->errorResponse('Akses ditolak. Hanya petugas administrasi yang dapat memanggil ulang.', 403);
            }

            $queue = Queue::findOrFail($id);

            if ($queue->status !== 'examining') {
                return $this->errorResponse('Hanya antrean berstatus SEDANG DIPERIKSA yang dapat dipanggil ulang.', 400);
            }

            if ($queue->recall_count >= 3) {
                // Pasien tidak hadir setelah 3 kali panggilan → pindahkan ke urutan paling belakang (check_in_time di-update)
                return DB::transaction(function () use ($queue) {
                    \App\Models\Polyclinic::where('id', $queue->polyclinic_id)->lockForUpdate()->first();

                    $schedule = $queue->doctorSchedule;
                    if ($schedule) {
                        $endTime = \Carbon\Carbon::parse($queue->date . ' ' . $schedule->end_time);
                        if (\Carbon\Carbon::now()->greaterThan($endTime)) {
                            return $this->errorResponse('Gagal memanggil ulang. Waktu pelayanan untuk jadwal dokter ini telah berakhir.', 422);
                        }
                    }

                    $queue->update([
                        'status' => 'waiting',
                        'recall_count' => 0,
                        'called_time' => null,
                        'check_in_time' => now(),
                    ]);

                    Queue::recalculateEstimatedTimes($queue->polyclinic_id, $queue->date);

                    $updated = Queue::getById($queue->id);
                    return $this->successResponse(new QueueResource($updated), 'Batas 3 kali panggilan tercapai. Antrean dipindahkan ke urutan paling belakang.');
                });
            }

            $queue->increment('recall_count');

            return $this->successResponse(new QueueResource($queue), 'Panggilan ulang berhasil dilakukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data antrean tidak ditemukan', 404);
        }
    }

    public function skip(Request $request, $id) {
        try {
            $queue = Queue::findOrFail($id);
            $user = $request->user();

            if ($user->role === 'patient') {
                return $this->errorResponse('Akses ditolak. Pasien tidak memiliki otorisasi.', 403);
            }

            if ($queue->status !== 'waiting') {
                return $this->errorResponse('Hanya antrean dengan status "waiting" yang dapat digeser ke urutan paling belakang.', 422);
            }

            if (!$queue->check_in_time) {
                return $this->errorResponse('Antrean belum check-in. Hanya antrean yang sudah diverifikasi kehadirannya yang dapat digeser.', 422);
            }

            $timeError = $this->validateServiceTimeWindow($queue, $user, 'waiting');
            if ($timeError) {
                return $this->errorResponse($timeError, 422);
            }

            return DB::transaction(function () use ($queue) {
                // Lock row poliklinik
                \App\Models\Polyclinic::where('id', $queue->polyclinic_id)->lockForUpdate()->first();

                $queue->update([
                    'status' => 'waiting',
                    'recall_count' => 0,
                    'check_in_time' => now(), // updates check-in time to move to the back
                ]);

                Queue::recalculateEstimatedTimes($queue->polyclinic_id, $queue->date);

                $updated = Queue::getById($queue->id);
                return $this->successResponse(new QueueResource($updated), 'Antrean berhasil digeser ke urutan paling belakang');
            });
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menggeser antrean ke urutan paling belakang', 500);
        }
    }

    private function validateServiceTimeWindow($queue, $user = null, $newStatus = null) {
        if ($user) {
            // Admin can bypass time window validations entirely
            if ($user->role === 'admin') {
                return null;
            }
            // Doctor can bypass when completing the service or calling/examining the patient
            if ($user->role === 'doctor' && in_array($newStatus, ['examining', 'completed'])) {
                return null;
            }
        }

        // Allow cancellation at any time
        if ($newStatus === 'cancelled') {
            return null;
        }

        $today = \Carbon\Carbon::today()->toDateString();
        if ($queue->date !== $today) {
            return 'Aksi ditolak. Perubahan status hanya diijinkan pada hari kunjungan.';
        }

        if (!$queue->estimated_service_time) {
            return 'Aksi ditolak. Estimasi jam pelayanan belum tersedia untuk antrean ini.';
        }

        $now = \Carbon\Carbon::now();

        // Validasi waktu 2 jam murni menggunakan instance Carbon agar lintas-hari tertangani
        $estTime = \Carbon\Carbon::parse($queue->date . ' ' . $queue->estimated_service_time);
        
        // Diperbolehkan check-in kapan saja pada hari kunjungan sebelum batas akhir toleransi 2 jam terlewati
        if ($now->greaterThan($estTime->addHours(2))) {
            return 'Aksi ditolak. Batas waktu toleransi 2 jam setelah estimasi pelayanan telah terlewati.';
        }
        return null;
    }
}
