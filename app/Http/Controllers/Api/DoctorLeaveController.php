<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorLeave;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorLeaveController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = DoctorLeave::query();
        
        if ($user && $user->role !== 'admin') {
            $query->where('leave_date', '>=', now()->toDateString());
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $query->orderBy('leave_date', 'desc');

        return $this->successResponse($query->get(), 'Daftar cuti dokter berhasil diambil');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|integer|exists:doctors,id',
            'leave_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Cek cuti ganda
        $exists = DoctorLeave::where('doctor_id', $request->doctor_id)
            ->where('leave_date', $request->leave_date)
            ->exists();

        if ($exists) {
            return $this->errorResponse('Dokter sudah mengajukan cuti pada tanggal tersebut', 422);
        }

        $leave = DoctorLeave::create($validator->validated());

        $activeQueues = \App\Models\Queue::where('doctor_id', $leave->doctor_id)
            ->where('date', $leave->leave_date)
            ->whereIn('status', ['booked', 'waiting'])
            ->with(['patient.user', 'doctor.user'])
            ->get();

        foreach ($activeQueues as $queue) {
            $queue->update(['status' => 'cancelled']);
            
            $patientUser = $queue->patient->user ?? null;
            if ($patientUser && $patientUser->fcm_token) {
                try {
                    $firebaseService = new \App\Services\FirebaseNotificationService();
                    $title = "Antrean Dibatalkan Otomatis";
                    $doctorName = $queue->doctor->user->name ?? 'Dokter';
                    $body = "Antrean Anda dengan " . $doctorName . " pada tanggal " . $leave->leave_date . " telah dibatalkan oleh sistem karena dokter bersangkutan mengambil cuti: " . ($leave->reason ?? 'Cuti Tahunan');
                    $firebaseService->sendToToken($patientUser->fcm_token, $title, $body, [
                        'type' => 'queue_cancellation',
                        'queue_id' => $queue->id
                    ]);
                } catch (\Exception $e) {
                    // Ignore FCM send errors
                }
            }
        }

        if ($activeQueues->isNotEmpty()) {
            $firstQueue = $activeQueues->first();
            \App\Models\Queue::recalculateEstimatedTimes($firstQueue->polyclinic_id, $leave->leave_date);
        }

        return $this->successResponse($leave, 'Cuti dokter berhasil ditambahkan dan antrean aktif pada tanggal tersebut telah dibatalkan otomatis.', 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'sometimes|required|integer|exists:doctors,id',
            'leave_date' => 'sometimes|required|date',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $leave = DoctorLeave::findOrFail($id);
        $leave->update($validator->validated());

        return $this->successResponse($leave, 'Cuti dokter berhasil diperbarui');
    }

    public function destroy($id)
    {
        $leave = DoctorLeave::findOrFail($id);
        $leave->delete();

        return $this->successResponse(null, 'Cuti dokter berhasil dihapus');
    }
}
