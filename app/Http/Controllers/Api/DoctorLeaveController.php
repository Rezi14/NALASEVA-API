<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorLeave;
use App\Services\DoctorService;
use App\Traits\ApiResponse;
use App\Http\Requests\StoreDoctorLeaveRequest;
use App\Http\Requests\UpdateDoctorLeaveRequest;
use App\Http\Resources\DoctorLeaveResource;
use Illuminate\Http\Request;
use Exception;

class DoctorLeaveController extends Controller
{
    use ApiResponse;

    protected DoctorService $doctorService;

    public function __construct(DoctorService $doctorService)
    {
        $this->doctorService = $doctorService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = DoctorLeave::with(['doctor.user', 'doctor.polyclinic']);
        
        if ($user && $user->role !== 'admin') {
            $query->where('leave_date', '>=', now()->toDateString());
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $query->orderBy('leave_date', 'desc');

        return $this->successResponse(DoctorLeaveResource::collection($query->get()), 'Daftar cuti dokter berhasil diambil');
    }

    public function show($id)
    {
        try {
            $leave = DoctorLeave::with(['doctor.user', 'doctor.polyclinic'])->findOrFail($id);
            return $this->successResponse(new DoctorLeaveResource($leave), 'Detail cuti dokter ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data cuti dokter tidak ditemukan', 404);
        }
    }

    public function store(StoreDoctorLeaveRequest $request)
    {
        try {
            $leave = $this->doctorService->storeLeave($request->validated());
            return $this->successResponse(new DoctorLeaveResource($leave), 'Cuti dokter berhasil ditambahkan dan antrean aktif pada tanggal tersebut telah dibatalkan otomatis.', 201);
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 422 ? 422 : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function update(UpdateDoctorLeaveRequest $request, $id)
    {
        try {
            $leave = DoctorLeave::findOrFail($id);
            $leave->update($request->validated());
            return $this->successResponse(new DoctorLeaveResource($leave), 'Cuti dokter berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data tidak ditemukan', 404);
        }
    }

    public function destroy($id)
    {
        try {
            $leave = DoctorLeave::findOrFail($id);
            $leave->delete();
            return $this->successResponse(null, 'Cuti dokter berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus, data tidak ditemukan', 404);
        }
    }
}
