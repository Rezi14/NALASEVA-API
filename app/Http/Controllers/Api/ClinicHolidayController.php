<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicHoliday;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        return $this->successResponse($holidays, 'Daftar hari libur berhasil diambil');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'holiday_date' => 'required|date|unique:clinic_holidays,holiday_date',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $holiday = ClinicHoliday::create($validator->validated());

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
                    // Ignore FCM send errors
                }
            }
        }

        // Group by polyclinic and recalculate
        $polyDates = $activeQueues->groupBy('polyclinic_id');
        foreach ($polyDates as $polyId => $queues) {
            \App\Models\Queue::recalculateEstimatedTimes($polyId, $holiday->holiday_date);
        }

        return $this->successResponse($holiday, 'Hari libur berhasil ditambahkan dan antrean aktif pada tanggal tersebut telah dibatalkan otomatis.', 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'holiday_date' => 'sometimes|required|date|unique:clinic_holidays,holiday_date,' . $id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $holiday = ClinicHoliday::findOrFail($id);
        $holiday->update($validator->validated());

        return $this->successResponse($holiday, 'Hari libur berhasil diperbarui');
    }

    public function destroy($id)
    {
        $holiday = ClinicHoliday::findOrFail($id);
        $holiday->delete();

        return $this->successResponse(null, 'Hari libur berhasil dihapus');
    }
}
