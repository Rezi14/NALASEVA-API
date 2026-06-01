<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\User;
use App\Models\DoctorSchedule;
use App\Models\DoctorLeave;
use App\Models\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class DoctorService
{
    public function updateStatus(Doctor $doctor, bool $isOnline): Doctor
    {
        $doctor->update(['is_online' => $isOnline]);
        return $doctor;
    }

    public function storeDoctor(array $data): Doctor
    {
        return DB::transaction(function() use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'doctor',
                'national_id' => $data['national_id'],
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'],
                'phone' => $data['phone'],
                'address' => $data['address'],
            ]);

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'polyclinic_id' => $data['polyclinic_id'],
                'specialization' => $data['specialization'],
                'license_number' => $data['license_number'],
            ]);

            return $doctor;
        });
    }

    public function updateDoctor(Doctor $doctor, array $data): Doctor
    {
        // Validasi: Jika polyclinic_id berubah, pastikan dokter tidak memiliki antrean aktif di poliklinik lama
        if (isset($data['polyclinic_id']) && $data['polyclinic_id'] != $doctor->polyclinic_id) {
            $hasActiveQueues = Queue::where('doctor_id', $doctor->id)
                                                ->whereIn('status', ['booked', 'waiting', 'examining'])
                                                ->exists();
            if ($hasActiveQueues) {
                throw new Exception('Gagal memperbarui poliklinik dokter. Dokter masih memiliki antrean aktif. Silakan selesaikan atau batalkan antrean terlebih dahulu.', 422);
            }
        }

        return DB::transaction(function() use ($data, $doctor) {
            $userData = collect($data)->only(['name', 'national_id', 'gender', 'birth_date', 'phone', 'address'])->toArray();
            if (!empty($userData) && $doctor->user) {
                $doctor->user->update($userData);
            }
            
            $doctorData = collect($data)->only(['polyclinic_id', 'specialization', 'license_number'])->toArray();
            if (!empty($doctorData)) {
                $doctor->update($doctorData);
            }
            
            return $doctor;
        });
    }

    public function deleteDoctor(Doctor $doctor): void
    {
        $hasActiveQueues = Queue::where('doctor_id', $doctor->id)
                                            ->whereIn('status', ['booked', 'waiting', 'examining'])
                                            ->exists();
        if ($hasActiveQueues) {
            throw new Exception('Gagal menghapus dokter. Dokter masih memiliki antrean aktif yang belum selesai/terminal.', 422);
        }
        
        DB::transaction(function() use ($doctor) {
            $userId = $doctor->user_id;
            $doctor->delete();
            User::where('id', $userId)->delete();
        });
    }

    public function restoreDoctor(int $id): void
    {
        DB::transaction(function() use ($id) {
            $doctor = Doctor::onlyTrashed()->findOrFail($id);
            $doctor->restore();
            User::onlyTrashed()->where('id', $doctor->user_id)->restore();
        });
    }

    public function storeSchedule(array $data): DoctorSchedule
    {
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        $overlapExists = DoctorSchedule::where('doctor_id', $data['doctor_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where(function($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();
            
        if ($overlapExists) {
            throw new Exception('Dokter ini sudah memiliki jadwal praktik yang bertabrakan pada jam tersebut di hari ' . $data['day_of_week'], 422);
        }

        return DoctorSchedule::create($data);
    }

    public function updateSchedule(int $id, array $data): DoctorSchedule
    {
        $schedule = DoctorSchedule::findOrFail($id);
        $doctorId = $data['doctor_id'] ?? $schedule->doctor_id;
        $dayOfWeek = $data['day_of_week'] ?? $schedule->day_of_week;
        $startTime = $data['start_time'] ?? $schedule->start_time;
        $endTime = $data['end_time'] ?? $schedule->end_time;

        if ($dayOfWeek !== $schedule->day_of_week || $startTime !== $schedule->start_time || $endTime !== $schedule->end_time) {
            $hasActiveQueues = Queue::where('doctor_schedule_id', $id)
                                                ->whereIn('status', ['booked', 'waiting', 'examining'])
                                                ->exists();
            if ($hasActiveQueues) {
                throw new Exception('Gagal memperbarui jadwal. Masih ada antrean aktif yang menggunakan jadwal ini. Silakan selesaikan atau batalkan antrean terlebih dahulu.', 422);
            }
        }

        $overlapExists = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('id', '!=', $id)
            ->where(function($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($overlapExists) {
            throw new Exception('Pembaruan gagal, jadwal bertabrakan dengan shift lain pada hari ' . $dayOfWeek, 422);
        }

        $schedule->update($data);
        return $schedule;
    }

    public function deleteSchedule(int $id): void
    {
        $hasActiveQueues = Queue::where('doctor_schedule_id', $id)
                                             ->whereIn('status', ['booked', 'waiting', 'examining'])
                                             ->exists();
        if ($hasActiveQueues) {
            throw new Exception('Gagal menghapus jadwal. Masih ada antrean aktif yang terikat pada jadwal ini.', 422);
        }

        DoctorSchedule::findOrFail($id)->delete();
    }

    public function storeLeave(array $data): DoctorLeave
    {
        $exists = DoctorLeave::where('doctor_id', $data['doctor_id'])
            ->where('leave_date', $data['leave_date'])
            ->exists();

        if ($exists) {
            throw new Exception('Dokter sudah mengajukan cuti pada tanggal tersebut', 422);
        }

        return DB::transaction(function () use ($data) {
            $leave = DoctorLeave::create($data);

            $activeQueues = Queue::where('doctor_id', $leave->doctor_id)
                ->where('date', $leave->leave_date)
                ->whereIn('status', ['booked', 'waiting'])
                ->with(['patient.user', 'doctor.user'])
                ->get();

            foreach ($activeQueues as $queue) {
                $queue->update(['status' => 'cancelled']);
                
                $patientUser = $queue->patient->user ?? null;
                if ($patientUser && $patientUser->fcm_token) {
                    try {
                        $firebaseService = new FirebaseNotificationService();
                        $title = "Antrean Dibatalkan Otomatis";
                        $doctorName = $queue->doctor->user->name ?? 'Dokter';
                        $body = "Antrean Anda dengan " . $doctorName . " pada tanggal " . $leave->leave_date . " telah dibatalkan oleh sistem karena dokter bersangkutan mengambil cuti: " . ($leave->reason ?? 'Cuti Tahunan');
                        $firebaseService->sendToToken($patientUser->fcm_token, $title, $body, [
                            'type' => 'queue_cancellation',
                            'queue_id' => $queue->id
                        ]);
                    } catch (\Exception $e) {
                        Log::error('FCM Leave Cancellation Notification Error: ' . $e->getMessage(), [
                            'queue_id' => $queue->id,
                            'exception' => $e
                        ]);
                    }
                }
            }

            if ($activeQueues->isNotEmpty()) {
                $firstQueue = $activeQueues->first();
                Queue::recalculateEstimatedTimes($firstQueue->polyclinic_id, $leave->leave_date);
            }

            return $leave;
        });
    }
}
