<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Services\DoctorService;
use App\Traits\ApiResponse;
use App\Http\Requests\StoreDoctorScheduleRequest;
use App\Http\Requests\UpdateDoctorScheduleRequest;
use App\Http\Resources\DoctorScheduleResource;
use Illuminate\Http\Request;
use Exception;

class DoctorScheduleController extends Controller
{
    use ApiResponse;

    protected DoctorService $doctorService;

    public function __construct(DoctorService $doctorService)
    {
        $this->doctorService = $doctorService;
    }

    public function index(Request $request)
    {
        $query = DoctorSchedule::with(['doctor.user', 'doctor.polyclinic']);

        if ($request->has('polyclinic_id')) {
            $query->whereHas('doctor', function ($q) use ($request) {
                $q->where('polyclinic_id', $request->polyclinic_id);
            });
        }

        $schedules = $query->get();
        $date = $request->input('date', \Carbon\Carbon::today()->toDateString());

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

        return $this->successResponse(DoctorScheduleResource::collection($schedules), 'Daftar jadwal dokter berhasil diambil');
    }

    public function store(StoreDoctorScheduleRequest $request)
    {
        try {
            $data = $this->doctorService->storeSchedule($request->validated());
            return $this->successResponse(new DoctorScheduleResource($data), 'Jadwal dokter berhasil ditambahkan', 201);
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 422 ? 422 : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function show($id)
    {
        try {
            $schedule = DoctorSchedule::getById($id);
            return $this->successResponse(new DoctorScheduleResource($schedule), 'Detail jadwal ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data jadwal tidak ditemukan', 404);
        }
    }

    public function update(UpdateDoctorScheduleRequest $request, $id)
    {
        try {
            $updatedSchedule = $this->doctorService->updateSchedule($id, $request->validated());
            return $this->successResponse(new DoctorScheduleResource($updatedSchedule), 'Jadwal berhasil diperbarui');
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [404, 422]) ? $e->getCode() : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function destroy($id)
    {
        try {
            $this->doctorService->deleteSchedule($id);
            return $this->successResponse(null, 'Jadwal berhasil dihapus');
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [404, 422]) ? $e->getCode() : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function restore($id)
    {
        try {
            DoctorSchedule::restoreData($id);
            return $this->successResponse(null, 'Data jadwal berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan di tempat sampah', 404);
        }
    }
}