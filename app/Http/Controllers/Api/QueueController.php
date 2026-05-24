<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    use ApiResponse;

    public function index() {
        return $this->successResponse(Queue::getAll(), 'Daftar antrian berhasil diambil');
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'patient_id'    => 'required|integer|exists:patients,id',
            'polyclinic_id' => 'required|integer|exists:polyclinics,id',
            'doctor_id'     => 'required|integer|exists:doctors,id',
            'date'          => 'required|date',
            'is_priority'   => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(implode(', ', $validator->errors()->all()), 422);
        }

        $user = $request->user();

        // Mencegah Celah IDOR: Pasien tidak bisa mendaftarkan antrean atas nama patient_id orang lain
        if ($user->role === 'patient') {
            $patient = \App\Models\Patient::where('user_id', $user->id)->first();
            if (!$patient || $patient->id != $request->patient_id) {
                return $this->errorResponse('Akses ditolak. Anda tidak dapat mendaftarkan pasien lain.', 403);
            }
        }

        $existingQueue = Queue::where('patient_id', $request->patient_id)
                              ->where('polyclinic_id', $request->polyclinic_id)
                              ->where('date', $request->date)
                              ->whereIn('status', ['booked', 'waiting', 'examining'])
                              ->first();

        if ($existingQueue) {
            return $this->errorResponse('Anda sudah memiliki antrean aktif di poliklinik ini untuk tanggal tersebut', 422);
        }

        $bookingDate = \Carbon\Carbon::parse($request->date);
        $today = \Carbon\Carbon::today();
        $daysDiff = $today->diffInDays($bookingDate, false);
        
        if ($daysDiff < 1 || $daysDiff > 7) {
            return $this->errorResponse('Pendaftaran online hanya diperbolehkan H-7 hingga H-1 sebelum tanggal kunjungan', 422);
        }

        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $dayName = $days[$bookingDate->format('l')];
        
        $schedule = \App\Models\DoctorSchedule::where('doctor_id', $request->doctor_id)
                                              ->where('day_of_week', $dayName)
                                              ->first();

        if (!$schedule) {
            return $this->errorResponse('Dokter tidak memiliki jadwal praktik pada hari tersebut', 422);
        }

        // Kalkulasi kuota dinamis berdasarkan jam kerja dokter (durasi_menit / 15)
        $startTime = \Carbon\Carbon::parse($schedule->start_time);
        $endTime = \Carbon\Carbon::parse($schedule->end_time);
        $duration = $startTime->diffInMinutes($endTime);
        $quota = $duration > 0 ? floor($duration / 15) : 10;

        // Hitung antrean aktif saat ini pada tanggal terpilih
        $activeBookingsCount = Queue::where('doctor_id', $request->doctor_id)
                                    ->where('polyclinic_id', $request->polyclinic_id)
                                    ->where('date', $request->date)
                                    ->whereIn('status', ['booked', 'waiting', 'examining'])
                                    ->count();

        if ($activeBookingsCount >= $quota) {
            return $this->errorResponse('Kuota pendaftaran untuk jadwal dokter tersebut sudah penuh (Kapasitas: ' . $quota . ' pasien)', 422);
        }

        $polyclinic = \App\Models\Polyclinic::findOrFail($request->polyclinic_id);
        
        // Perbaikan Bug Prefix: Menggunakan kode poliklinik resmi dari database (misal: GIG, UM, dll)
        $prefix = strtoupper($polyclinic->code);
        
        try {
            return DB::transaction(function () use ($request, $prefix, $validator, $schedule) {
                // Perbaikan Race Condition: Lock row poliklinik agar lock tetap bekerja meski tabel antrean kosong
                \App\Models\Polyclinic::where('id', $request->polyclinic_id)->lockForUpdate()->first();

                $lastQueue = Queue::whereDate('date', $request->date)
                                   ->where('polyclinic_id', $request->polyclinic_id)
                                   ->orderBy('id', 'desc')
                                   ->first();
                                   
                $nextNumber = 1;
                if ($lastQueue && preg_match('/-(\d+)$/', $lastQueue->queue_number, $matches)) {
                     $nextNumber = (int)$matches[1] + 1;
                }
                
                if ($nextNumber < 1) {
                    $nextNumber = 1;
                }
                
                $queueNumber = sprintf('%s-%03d', $prefix, $nextNumber);

                // Perbaikan Mass Assignment: Hanya simpan data yang tervalidasi
                $data = $validator->validated();
                $data['queue_number'] = $queueNumber;
                $data['status'] = 'booked';

                $queue = Queue::storeData($data);
                return $this->successResponse(Queue::getById($queue->id), 'Antrian berhasil dibuat', 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan sistem saat mengambil nomor antrean', 500);
        }
    }

    public function show(Request $request, $id) {
        try {
            $queue = Queue::getById($id);
            $user = $request->user();

            if ($user->role === 'patient' && $queue->patient->user_id !== $user->id) {
                return $this->errorResponse('Akses ditolak. Anda tidak dapat melihat detail antrean orang lain.', 403);
            }

            return $this->successResponse($queue, 'Detail antrian ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data antrian tidak ditemukan', 404);
        }
    }

    public function update(Request $request, $id) {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|required|string'
            ]);

            $user = $request->user();
            // BUG-8 Security Fix: Hanya Admin dan Dokter yang bisa mengubah status antrean (IDOR Protection)
            if ($user->role === 'patient') {
                return $this->errorResponse('Akses ditolak. Pasien tidak diizinkan mengubah status antrean.', 403);
            }

            if ($validator->fails()) {
                return $this->errorResponse(implode(', ', $validator->errors()->all()), 422);
            }

            $validatedData = $validator->validated();
            if (isset($validatedData['status']) && $validatedData['status'] === 'examining') {
                $queueToUpdate = Queue::findOrFail($id);
                $existingExamining = Queue::where('polyclinic_id', $queueToUpdate->polyclinic_id)
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

            // Perbaikan Mass Assignment Bypass
            $data = Queue::updateData($id, $validatedData);

            if (isset($validatedData['status']) && $validatedData['status'] === 'examining') {
                $updatedQueue = Queue::with('patient.user')->find($id);
                $fcmToken = $updatedQueue->patient->user->fcm_token ?? null;
                
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

            return $this->successResponse(Queue::getById($id), 'Status antrian berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data antrian tidak ditemukan', 404);
        }
    }

    public function destroy(Request $request, $id) {
        try {
            $queue = Queue::findOrFail($id);
            $user = $request->user();

            // Mencegah Celah Keamanan IDOR: Pasien tidak boleh menghapus antrean milik orang lain
            if ($user->role === 'patient' && $queue->patient->user_id !== $user->id) {
                return $this->errorResponse('Akses ditolak. Anda hanya dapat membatalkan antrean Anda sendiri.', 403);
            }

            // BUG-9 Integrity Fix: Hanya bisa membatalkan antrean yang belum diproses (booked)
            if ($queue->status !== 'booked') {
                return $this->errorResponse('Antrean yang sedang diperiksa atau sudah selesai tidak dapat dibatalkan.', 422);
            }

            $queue->update(['status' => 'cancelled']);
            return $this->successResponse($queue, 'Antrian berhasil dibatalkan');
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
            // Perbaikan Keamanan: Hanya ADMIN yang boleh mengubah status check-in via scanner pendaftaran
            if ($request->user()->role !== 'admin') {
                return $this->errorResponse('Akses ditolak. Hanya petugas administrasi yang dapat memverifikasi Check-in.', 403);
            }

            $queue = Queue::findOrFail($id);
            
            if (!\Carbon\Carbon::parse($queue->date)->isToday()) {
                return $this->errorResponse('Check-in hanya dapat dilakukan pada tanggal pendaftaran (' . $queue->date . ')', 400);
            }

            if ($queue->status !== 'booked') {
                return $this->errorResponse('Antrean sudah check-in atau tidak valid', 400);
            }

            $queue->update([
                'status' => 'waiting',
                'check_in_time' => now()
            ]);

            return $this->successResponse($queue, 'Check-in berhasil via QR Scanner');
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

            return DB::transaction(function () use ($queue) {
                // Lock row poliklinik
                \App\Models\Polyclinic::where('id', $queue->polyclinic_id)->lockForUpdate()->first();

                $lastQueue = Queue::whereDate('date', $queue->date)
                                   ->where('polyclinic_id', $queue->polyclinic_id)
                                   ->orderBy('id', 'desc')
                                   ->first();
                                   
                $nextNumber = 1;
                if ($lastQueue && preg_match('/-(\d+)$/', $lastQueue->queue_number, $matches)) {
                     $nextNumber = (int)$matches[1] + 1;
                }
                
                if ($nextNumber < 1) {
                    $nextNumber = 1;
                }
                
                $polyclinic = \App\Models\Polyclinic::findOrFail($queue->polyclinic_id);
                $prefix = strtoupper($polyclinic->code);
                $newQueueNumber = sprintf('%s-%03d', $prefix, $nextNumber);

                $queue->update([
                    'queue_number' => $newQueueNumber,
                    'status' => 'booked', // kembalikan ke status booked agar harus check-in kembali
                ]);

                return $this->successResponse(Queue::getById($queue->id), 'Antrean berhasil digeser ke urutan paling belakang');
            });
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menggeser antrean ke urutan paling belakang', 500);
        }
    }
}