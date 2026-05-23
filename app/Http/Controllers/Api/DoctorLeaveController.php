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
        $query = DoctorLeave::where('leave_date', '>=', now()->toDateString());

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

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
        return $this->successResponse($leave, 'Cuti dokter berhasil ditambahkan', 201);
    }
}
