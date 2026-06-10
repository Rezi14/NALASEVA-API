<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicHoliday;
use App\Traits\ApiResponse;
use App\Http\Requests\StoreClinicHolidayRequest;
use App\Http\Requests\UpdateClinicHolidayRequest;
use App\Http\Resources\ClinicHolidayResource;
use Illuminate\Http\Request;
use Exception;

class ClinicHolidayController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = ClinicHoliday::query();
        if ($user && $user->role !== 'admin') {
            $query->where('holiday_date', '>=', now()->toDateString());
        }
        $holidays = $query->orderBy('holiday_date', 'desc')->get();
        return $this->successResponse(ClinicHolidayResource::collection($holidays), 'Daftar hari libur berhasil diambil');
    }

    public function show($id)
    {
        try {
            $holiday = ClinicHoliday::findOrFail($id);
            return $this->successResponse(new ClinicHolidayResource($holiday), 'Detail hari libur ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data hari libur tidak ditemukan', 404);
        }
    }

    public function store(StoreClinicHolidayRequest $request)
    {
        $holiday = ClinicHoliday::create($request->validated());

        $activeQueues = \App\Models\Queue::where('date', $holiday->holiday_date)
            ->whereIn('status', ['booked', 'waiting'])
            ->with('patient.user')
            ->get();

        foreach ($activeQueues as $queue) {
            $queue->update(['status' => 'cancelled']);
            
            $patientUser = $queue->patient->user ?? null;
            if ($patientUser && $patientUser->fcm_token) {
                try {
                    $firebaseService = new \App\Services\FirebaseNotificationService();
                    $title = "Antrean Dibatalkan Otomatis";
                    $body = "Antrean Anda pada tanggal " . $holiday->holiday_date . " telah dibatalkan oleh sistem karena Puskesmas libur operasional: " . ($holiday->description ?? 'Hari Libur');
                    $firebaseService->sendToToken($patientUser->fcm_token, $title, $body, [
                        'type' => 'queue_cancellation',
                        'queue_id' => $queue->id
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('FCM Holiday Cancellation Notification Error: ' . $e->getMessage(), [
                        'queue_id' => $queue->id,
                        'exception' => $e
                    ]);
                }
            }
        }

        $polyDates = $activeQueues->groupBy('polyclinic_id');
        foreach ($polyDates as $polyId => $queues) {
            \App\Models\Queue::recalculateEstimatedTimes($polyId, $holiday->holiday_date);
        }

        return $this->successResponse(new ClinicHolidayResource($holiday), 'Hari libur berhasil ditambahkan dan antrean aktif pada tanggal tersebut telah dibatalkan otomatis.', 201);
    }

    public function update(UpdateClinicHolidayRequest $request, $id)
    {
        try {
            $holiday = ClinicHoliday::findOrFail($id);
            $holiday->update($request->validated());
            return $this->successResponse(new ClinicHolidayResource($holiday), 'Hari libur berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data hari libur tidak ditemukan', 404);
        }
    }

    public function destroy($id)
    {
        try {
            $holiday = ClinicHoliday::findOrFail($id);
            $holiday->delete();
            return $this->successResponse(null, 'Hari libur berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus, data hari libur tidak ditemukan', 404);
        }
    }
}
