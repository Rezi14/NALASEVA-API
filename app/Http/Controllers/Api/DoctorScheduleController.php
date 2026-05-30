<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class DoctorScheduleController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = DoctorSchedule::with(['doctor.user', 'doctor.polyclinic']);

        if ($request->has('polyclinic_id')) {
            $query->whereHas('doctor', function ($q) use ($request) {
                $q->where('polyclinic_id', $request->polyclinic_id);
            });
        }

        $schedules = $query->get();

        if ($request->has('date')) {
            $date = $request->date;
            $schedules->map(function ($s) use ($date) {
                $startTime = \Carbon\Carbon::parse($s->start_time);
                $endTime = \Carbon\Carbon::parse($s->end_time);
                $duration = $startTime->diffInMinutes($endTime);
                $scheduleQuota = $duration > 0 ? floor($duration / 15) : 10;

                $activeBookingsOnSchedule = \App\Models\Queue::where('doctor_schedule_id', $s->id)
                                                           ->where('date', $date)
                                                           ->whereNotIn('status', ['cancelled'])
                                                           ->count();
                $remainingScheduleLimit = max(0, $scheduleQuota - $activeBookingsOnSchedule);

                $s->remaining_daily_quota = $remainingScheduleLimit;
                return $s;
            });
        } else {
            // Default fallback using today's date if no date parameter is supplied
            $today = \Carbon\Carbon::today()->toDateString();
            $schedules->map(function ($s) use ($today) {
                $startTime = \Carbon\Carbon::parse($s->start_time);
                $endTime = \Carbon\Carbon::parse($s->end_time);
                $duration = $startTime->diffInMinutes($endTime);
                $scheduleQuota = $duration > 0 ? floor($duration / 15) : 10;

                $activeBookingsOnSchedule = \App\Models\Queue::where('doctor_schedule_id', $s->id)
                                                           ->where('date', $today)
                                                           ->whereNotIn('status', ['cancelled'])
                                                           ->count();
                $remainingScheduleLimit = max(0, $scheduleQuota - $activeBookingsOnSchedule);

                $s->remaining_daily_quota = $remainingScheduleLimit;
                return $s;
            });
        }

        return $this->successResponse($schedules, 'Daftar jadwal dokter berhasil diambil');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|integer|exists:doctors,id',
            'day_of_week' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $startTime = $request->start_time;
        $endTime = $request->end_time;

        // Cek apakah ada jadwal yang jamnya bertabrakan di hari yang sama
        $overlapExists = \App\Models\DoctorSchedule::where('doctor_id', $request->doctor_id)
            ->where('day_of_week', $request->day_of_week)
            ->where(function($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();
            
        if ($overlapExists) {
            return $this->errorResponse('Dokter ini sudah memiliki jadwal praktik yang bertabrakan pada jam tersebut di hari ' . $request->day_of_week, 422);
        }

        $data = DoctorSchedule::storeData($validator->validated());
        return $this->successResponse($data, 'Jadwal dokter berhasil ditambahkan', 201);
    }

    public function show($id)
    {
        try {
            $schedule = DoctorSchedule::getById($id);
            return $this->successResponse($schedule, 'Detail jadwal ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data jadwal tidak ditemukan', 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'sometimes|required|integer|exists:doctors,id',
                'day_of_week' => 'sometimes|required|string',
                'start_time' => 'sometimes|required|date_format:H:i',
                'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $schedule = \App\Models\DoctorSchedule::findOrFail($id);
            $doctorId = $request->doctor_id ?? $schedule->doctor_id;
            $dayOfWeek = $request->day_of_week ?? $schedule->day_of_week;
            $startTime = $request->start_time ?? $schedule->start_time;
            $endTime = $request->end_time ?? $schedule->end_time;

            // Validasi: Jika hari atau jam jadwal praktik diubah, pastikan tidak ada antrean aktif yang merujuk ke jadwal ini
            if ($dayOfWeek !== $schedule->day_of_week || $startTime !== $schedule->start_time || $endTime !== $schedule->end_time) {
                $hasActiveQueues = \App\Models\Queue::where('doctor_schedule_id', $id)
                                                    ->whereIn('status', ['booked', 'waiting', 'examining'])
                                                    ->exists();
                if ($hasActiveQueues) {
                    return $this->errorResponse('Gagal memperbarui jadwal. Masih ada antrean aktif yang menggunakan jadwal ini. Silakan selesaikan atau batalkan antrean terlebih dahulu.', 422);
                }
            }

            // Validasi tabrakan jam jika ada update
            $overlapExists = \App\Models\DoctorSchedule::where('doctor_id', $doctorId)
                ->where('day_of_week', $dayOfWeek)
                ->where('id', '!=', $id)
                ->where(function($query) use ($startTime, $endTime) {
                    $query->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                })
                ->exists();

            if ($overlapExists) {
                return $this->errorResponse('Pembaruan gagal, jadwal bertabrakan dengan shift lain pada hari ' . $dayOfWeek, 422);
            }

            $updatedSchedule = DoctorSchedule::updateData($id, $validator->validated());
            return $this->successResponse($updatedSchedule, 'Jadwal berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data jadwal tidak ditemukan', 404);
        }
    }

    public function destroy($id)
    {
        try {
            $hasActiveQueues = \App\Models\Queue::where('doctor_schedule_id', $id)
                                                ->whereIn('status', ['booked', 'waiting', 'examining'])
                                                ->exists();
            if ($hasActiveQueues) {
                return $this->errorResponse('Gagal menghapus jadwal. Masih ada antrean aktif yang terikat pada jadwal ini.', 422);
            }

            DoctorSchedule::softDeleteData($id);
            return $this->successResponse(null, 'Jadwal berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus, data jadwal tidak ditemukan', 404);
        }
    }

    public function restore($id) {
        try {
            DoctorSchedule::restoreData($id);
            return $this->successResponse(null, 'Data jadwal berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan di tempat sampah', 404);
        }
    }
}