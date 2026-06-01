<?php

namespace App\Services;

use App\Models\Queue;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ClinicHoliday;
use App\Models\DoctorLeave;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class QueueService
{
    public function getQueuesQuery(User $user)
    {
        $query = Queue::with(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);

        if ($user->role === 'patient') {
            $query->whereHas('patient', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->role === 'doctor') {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if ($doctor) {
                $query->where('doctor_id', $doctor->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public function storeQueue(User $user, array $data): Queue
    {
        // Prevent IDOR
        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first();
            if (!$patient || $patient->id != $data['patient_id']) {
                throw new Exception('Akses ditolak. Anda tidak dapat mendaftarkan pasien lain.', 403);
            }
        }

        $bookingDate = Carbon::parse($data['date'])->startOfDay();
        $today = Carbon::today()->startOfDay();
        $daysDiff = $today->diffInDays($bookingDate, false);

        if ($daysDiff < 0 || $daysDiff > 7) {
            throw new Exception('Pendaftaran online hanya diperbolehkan H-7 sampai hari ini sebelum tanggal kunjungan', 422);
        }

        return DB::transaction(function () use ($data, $bookingDate) {
            $patient = Patient::where('id', $data['patient_id'])->lockForUpdate()->first();
            if (!$patient) {
                throw new Exception('Pasien tidak ditemukan.', 404);
            }

            // Auto-detect age for priority status
            $isPriority = false;
            $patientUser = $patient->user;
            if ($patientUser && $patientUser->birth_date) {
                $age = Carbon::parse($patientUser->birth_date)->age;
                $isPriority = $age >= 60;
            }

            $polyclinic = Polyclinic::where('id', $data['polyclinic_id'])->lockForUpdate()->first();
            if (!$polyclinic) {
                throw new Exception('Poliklinik tidak ditemukan.', 404);
            }

            // 1. Holiday Validation
            if (ClinicHoliday::where('holiday_date', $data['date'])->exists()) {
                throw new Exception('Pendaftaran gagal. Puskesmas libur operasional pada tanggal tersebut.', 422);
            }

            // 2. Doctor Leave Validation
            if (DoctorLeave::where('doctor_id', $data['doctor_id'])->where('leave_date', $data['date'])->exists()) {
                throw new Exception('Pendaftaran gagal. Dokter yang bersangkutan sedang cuti pada tanggal tersebut.', 422);
            }

            // 3. Schedule Match Validation
            $schedule = DoctorSchedule::where('id', $data['doctor_schedule_id'])
                                      ->where('doctor_id', $data['doctor_id'])
                                      ->whereHas('doctor', function($q) use ($data) {
                                          $q->where('polyclinic_id', $data['polyclinic_id']);
                                      })
                                      ->first();
            if (!$schedule) {
                throw new Exception('Jadwal dokter tidak cocok dengan poliklinik atau dokter yang dipilih.', 422);
            }

            // 4. Duplicate Active Queue for Polyclinic Validation
            $existingQueueSamePoly = Queue::where('patient_id', $data['patient_id'])
                                          ->where('polyclinic_id', $data['polyclinic_id'])
                                          ->where('date', $data['date'])
                                          ->whereNotIn('status', ['cancelled'])
                                          ->lockForUpdate()
                                          ->first();
            if ($existingQueueSamePoly) {
                throw new Exception('Anda sudah memiliki antrean aktif di poliklinik ini untuk tanggal tersebut', 422);
            }

            // 5. Time Conflict Validation across polyclinics
            $activeQueuesSameDate = Queue::where('patient_id', $data['patient_id'])
                                         ->where('date', $data['date'])
                                         ->whereNotIn('status', ['cancelled'])
                                         ->lockForUpdate()
                                         ->get();

            $startNew = $schedule->start_time;
            $endNew = $schedule->end_time;

            foreach ($activeQueuesSameDate as $existingQueue) {
                $existingSchedule = DoctorSchedule::find($existingQueue->doctor_schedule_id);
                if ($existingSchedule) {
                    $startExist = $existingSchedule->start_time;
                    $endExist = $existingSchedule->end_time;

                    if ($startNew < $endExist && $startExist < $endNew) {
                        throw new Exception('Pendaftaran gagal. Jam pelayanan dokter bentrok dengan antrean aktif Anda yang lain pada hari tersebut (' . substr($startExist, 0, 5) . ' - ' . substr($endExist, 0, 5) . ' WIB).', 422);
                    }
                }
            }

            $days = [
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
            ];
            $dayName = $days[$bookingDate->format('l')];

            if ($schedule->day_of_week !== $dayName) {
                throw new Exception('Hari terpilih tidak cocok dengan hari praktik jadwal dokter tersebut.', 422);
            }

            if ($bookingDate->isSameDay(Carbon::now())) {
                $serviceStartTime = Carbon::parse($data['date'] . ' ' . $schedule->start_time);
                if (Carbon::now()->greaterThanOrEqualTo($serviceStartTime)) {
                    throw new Exception('Pendaftaran untuk hari ini hanya bisa dilakukan sebelum jam mulai praktik dokter.', 422);
                }
            }

            // Dynamic capacity calculations
            $startTime = Carbon::parse($schedule->start_time);
            $endTime = Carbon::parse($schedule->end_time);
            $duration = $startTime->diffInMinutes($endTime);
            $quota = $duration > 0 ? floor($duration / 15) : 10;

            $activeBookingsCount = Queue::where('doctor_schedule_id', $data['doctor_schedule_id'])
                                         ->where('date', $data['date'])
                                         ->whereNotIn('status', ['cancelled'])
                                         ->lockForUpdate()
                                         ->count();

            if ($activeBookingsCount >= $quota) {
                throw new Exception('Kuota pendaftaran untuk jadwal dokter tersebut sudah penuh (Kapasitas: ' . $quota . ' pasien)', 422);
            }

            $prefix = strtoupper($polyclinic->code);
            $lastQueue = Queue::withTrashed()
                               ->whereDate('date', $data['date'])
                               ->where('polyclinic_id', $polyclinic->id)
                               ->orderBy('id', 'desc')
                               ->first();
            $maxNumber = 0;
            if ($lastQueue && preg_match('/(\d+)$/', $lastQueue->queue_number, $m)) {
                $maxNumber = (int)$m[1];
            }

            $nextNumber = $maxNumber + 1;
            $queueNumber = sprintf('%s-%03d', $prefix, $nextNumber);

            $data['queue_number'] = $queueNumber;
            $data['status'] = 'booked';
            $data['is_priority'] = $isPriority;

            $queue = Queue::create($data);
            $est = Queue::calculateEstimatedServiceTime($queue);
            if ($est) {
                $queue->update(['estimated_service_time' => $est]);
            }
            Queue::recalculateEstimatedTimes($queue->polyclinic_id, $queue->date);

            $queue->load(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);
            return $queue;
        });
    }

    public function updateQueue(User $user, int $id, array $data): Queue
    {
        if ($user->role === 'patient') {
            throw new Exception('Akses ditolak. Pasien tidak diizinkan mengubah status antrean.', 403);
        }

        $queueToUpdate = Queue::findOrFail($id);

        if ($user->role === 'doctor') {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if (!$doctor || $queueToUpdate->doctor_id !== $doctor->id) {
                throw new Exception('Akses ditolak. Dokter hanya dapat mengubah status antrean miliknya sendiri.', 403);
            }
        }

        $newStatus = $data['status'] ?? null;
        $timeError = $this->validateServiceTimeWindow($queueToUpdate, $user, $newStatus);
        if ($timeError) {
            throw new Exception($timeError, 422);
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
                throw new Exception("Transisi status tidak valid dari '$oldStatus' ke '$newStatus'.", 422);
            }

            if ($newStatus === 'cancelled') {
                if ($user->role === 'doctor') {
                    throw new Exception('Akses ditolak. Dokter tidak diizinkan membatalkan antrean.', 403);
                }
                if (!in_array($queueToUpdate->status, ['booked', 'waiting'])) {
                    throw new Exception('Hanya antrean dengan status dipesan atau menunggu yang dapat dibatalkan.', 422);
                }
            }
        }

        if (isset($data['status']) && $data['status'] === 'examining') {
            $existingExamining = Queue::where('polyclinic_id', $queueToUpdate->polyclinic_id)
                ->where('doctor_id', $queueToUpdate->doctor_id)
                ->where('date', $queueToUpdate->date)
                ->where('status', 'examining')
                ->where('id', '!=', $id)
                ->exists();

            if ($existingExamining) {
                throw new Exception('Tidak dapat memanggil pasien. Masih ada pasien yang sedang diperiksa di poliklinik ini.', 422);
            }

            $doctor = Doctor::find($queueToUpdate->doctor_id);
            if ($doctor && !$doctor->is_online) {
                throw new Exception('Tidak dapat memanggil pasien. Dokter yang bersangkutan sedang beristirahat/offline.', 422);
            }

            $data['called_time'] = now();
        }

        $queueToUpdate->update($data);
        Queue::recalculateEstimatedTimes($queueToUpdate->polyclinic_id, $queueToUpdate->date);

        if (isset($data['status']) && $data['status'] === 'examining') {
            $updatedQueue = Queue::with('patient.user')->find($id);
            $fcmToken = $updatedQueue->patient?->user?->fcm_token ?? null;

            if ($fcmToken) {
                try {
                    $firebaseService = new FirebaseNotificationService();
                    $title = "Giliran Anda!";
                    $body = "Silakan masuk ke ruangan dokter sekarang (Nomor Antrean: {$updatedQueue->queue_number}).";
                    $firebaseService->sendToToken($fcmToken, $title, $body, [
                        'queue_id' => $id,
                        'status' => 'examining'
                    ]);
                } catch (\Exception $e) {
                    Log::error('FCM Queue Notification Error: ' . $e->getMessage(), [
                        'queue_id' => $id,
                        'exception' => $e
                    ]);
                }
            }
        }

        return $queueToUpdate->load(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);
    }

    public function destroyQueue(User $user, int $id): Queue
    {
        $queue = Queue::findOrFail($id);

        if ($user->role === 'doctor') {
            throw new Exception('Akses ditolak. Dokter tidak diizinkan membatalkan antrean.', 403);
        }

        if ($user->role === 'patient') {
            if (($queue->patient?->user_id ?? null) !== $user->id) {
                throw new Exception('Akses ditolak. Anda hanya dapat membatalkan antrean Anda sendiri.', 403);
            }

            $schedule = $queue->doctorSchedule;
            if ($schedule) {
                $serviceTime = Carbon::parse($queue->date . ' ' . $schedule->start_time);
                $isRecentlyCreated = $queue->created_at && Carbon::parse($queue->created_at)->diffInMinutes(now()) <= 15;
                
                if ($isRecentlyCreated && now()->greaterThanOrEqualTo($serviceTime)) {
                    throw new Exception('Pembatalan gagal. Waktu pelayanan dokter sudah dimulai/terlewat.', 422);
                }
                
                if (!$isRecentlyCreated && now()->greaterThanOrEqualTo($serviceTime->subHours(2))) {
                    throw new Exception('Pembatalan gagal. Antrean tidak dapat dibatalkan kurang dari 2 jam sebelum pelayanan dimulai.', 422);
                }
            }
        }

        if (!in_array($queue->status, ['booked', 'waiting'])) {
            throw new Exception('Hanya antrean dengan status dipesan atau menunggu yang dapat dibatalkan.', 422);
        }

        $queue->update(['status' => 'cancelled']);
        Queue::recalculateEstimatedTimes($queue->polyclinic_id, $queue->date);
        $queue->load(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);
        return $queue;
    }

    public function restoreQueue(int $id): void
    {
        Queue::onlyTrashed()->findOrFail($id)->restore();
    }

    public function checkInQueue(User $user, int $id, ?string $reason): Queue
    {
        if ($user->role !== 'admin') {
            throw new Exception('Akses ditolak. Hanya petugas administrasi yang dapat memverifikasi Check-in.', 403);
        }

        $queue = Queue::findOrFail($id);

        if (!Carbon::parse($queue->date)->isToday()) {
            throw new Exception('Check-in hanya dapat dilakukan pada tanggal pendaftaran (' . $queue->date . ')', 400);
        }

        $timeError = $this->validateServiceTimeWindow($queue, $user, 'waiting');
        if ($timeError) {
            throw new Exception($timeError, 422);
        }

        if ($queue->status !== 'booked') {
            throw new Exception('Antrean sudah check-in atau tidak valid', 400);
        }

        $queue->update([
            'status' => 'waiting',
            'check_in_time' => now(),
            'reason' => $reason
        ]);
        Queue::recalculateEstimatedTimes($queue->polyclinic_id, $queue->date);

        return $queue->load(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);
    }

    public function recallQueue(User $user, int $id): Queue
    {
        if ($user->role !== 'admin') {
            throw new Exception('Akses ditolak. Hanya petugas administrasi yang dapat memanggil ulang.', 403);
        }

        $queue = Queue::findOrFail($id);

        if ($queue->status !== 'examining') {
            throw new Exception('Hanya antrean berstatus SEDANG DIPERIKSA yang dapat dipanggil ulang.', 400);
        }

        if ($queue->recall_count >= 3) {
            return DB::transaction(function () use ($queue) {
                Polyclinic::where('id', $queue->polyclinic_id)->lockForUpdate()->first();

                $schedule = $queue->doctorSchedule;
                if ($schedule) {
                    $endTime = Carbon::parse($queue->date . ' ' . $schedule->end_time);
                    if (Carbon::now()->greaterThan($endTime)) {
                        throw new Exception('Gagal memanggil ulang. Waktu pelayanan untuk jadwal dokter ini telah berakhir.', 422);
                    }
                }

                $queue->update([
                    'status' => 'waiting',
                    'recall_count' => 0,
                    'called_time' => null,
                    'check_in_time' => now(),
                ]);

                Queue::recalculateEstimatedTimes($queue->polyclinic_id, $queue->date);

                return $queue->load(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);
            });
        }

        $queue->increment('recall_count');
        return $queue->load(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);
    }

    public function skipQueue(User $user, int $id): Queue
    {
        $queue = Queue::findOrFail($id);

        if ($user->role === 'patient') {
            throw new Exception('Akses ditolak. Pasien tidak memiliki otorisasi.', 403);
        }

        if ($queue->status !== 'waiting') {
            throw new Exception('Hanya antrean dengan status "waiting" yang dapat digeser ke urutan paling belakang.', 422);
        }

        if (!$queue->check_in_time) {
            throw new Exception('Antrean belum check-in. Hanya antrean yang sudah diverifikasi kehadirannya yang dapat digeser.', 422);
        }

        $timeError = $this->validateServiceTimeWindow($queue, $user, 'waiting');
        if ($timeError) {
            throw new Exception($timeError, 422);
        }

        return DB::transaction(function () use ($queue) {
            Polyclinic::where('id', $queue->polyclinic_id)->lockForUpdate()->first();

            $queue->update([
                'status' => 'waiting',
                'recall_count' => 0,
                'check_in_time' => now(),
            ]);

            Queue::recalculateEstimatedTimes($queue->polyclinic_id, $queue->date);

            return $queue->load(['patient.user', 'polyclinic', 'doctor.user', 'doctorSchedule']);
        });
    }

    private function validateServiceTimeWindow($queue, $user = null, $newStatus = null)
    {
        if ($user) {
            if ($user->role === 'admin') {
                return null;
            }
            if ($user->role === 'doctor' && in_array($newStatus, ['examining', 'completed'])) {
                return null;
            }
        }

        if ($newStatus === 'cancelled') {
            return null;
        }

        $today = Carbon::today()->toDateString();
        if ($queue->date !== $today) {
            return 'Aksi ditolak. Perubahan status hanya diijinkan pada hari kunjungan.';
        }

        if (!$queue->estimated_service_time) {
            return 'Aksi ditolak. Estimasi jam pelayanan belum tersedia untuk antrean ini.';
        }

        $now = Carbon::now();
        $estTime = Carbon::parse($queue->date . ' ' . $queue->estimated_service_time);
        
        if ($now->greaterThan($estTime->addHours(2))) {
            return 'Aksi ditolak. Batas waktu toleransi 2 jam setelah estimasi pelayanan telah terlewati.';
        }
        return null;
    }
}
