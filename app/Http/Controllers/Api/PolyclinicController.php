<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Polyclinic;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class PolyclinicController extends Controller
{
    use ApiResponse;

    public function index() {
        return $this->successResponse(Polyclinic::getAll(), 'Daftar poliklinik berhasil diambil');
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            // Mengabaikan data yang telah di-soft delete
            'code' => ['required', 'string', 'max:5', 'regex:/^[A-Z0-9]+$/', Rule::unique('polyclinics')->whereNull('deleted_at')],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $data = Polyclinic::storeData($validator->validated());
        return $this->successResponse($data, 'Poliklinik berhasil ditambahkan', 201);
    }

    public function show($id) {
        try {
            $data = Polyclinic::getById($id);
            return $this->successResponse($data, 'Detail poliklinik ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data poliklinik tidak ditemukan', 404);
        }
    }

    public function update(Request $request, $id) {
        try {
            $validator = Validator::make($request->all(), [
                'code' => ['sometimes', 'required', 'string', 'max:5', 'regex:/^[A-Z0-9]+$/', Rule::unique('polyclinics')->ignore($id)->whereNull('deleted_at')],
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $polyclinic = Polyclinic::findOrFail($id);
            if ($request->has('code') && $request->code !== $polyclinic->code) {
                $hasActiveQueues = \App\Models\Queue::where('polyclinic_id', $id)
                                                    ->whereDate('date', \Carbon\Carbon::today())
                                                    ->whereIn('status', ['booked', 'waiting', 'examining'])
                                                    ->exists();
                if ($hasActiveQueues) {
                    return $this->errorResponse('Gagal mengubah kode poliklinik. Masih ada antrean aktif pada poliklinik ini untuk hari berjalan.', 422);
                }
            }

            $data = Polyclinic::updateData($id, $validator->validated());
            return $this->successResponse($data, 'Poliklinik berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data poliklinik tidak ditemukan', 404);
        }
    }

    public function destroy($id) {
        try {
            // Validasi Bisnis: Mencegah penghapusan jika poliklinik masih memiliki antrean aktif
            $hasActiveQueues = \App\Models\Queue::where('polyclinic_id', $id)
                                                ->whereIn('status', ['booked', 'waiting', 'examining'])
                                                ->exists();
            if ($hasActiveQueues) {
                return $this->errorResponse('Gagal menghapus poliklinik. Masih ada antrean aktif pada poliklinik ini.', 422);
            }

            // Cek apakah masih ada jadwal dokter aktif yang terikat ke poliklinik ini
            $hasActiveSchedules = \App\Models\DoctorSchedule::whereHas('doctor', function($q) use ($id) {
                                                    $q->where('polyclinic_id', $id);
                                                })
                                                ->exists();
            if ($hasActiveSchedules) {
                return $this->errorResponse('Gagal menghapus poliklinik. Masih ada jadwal dokter yang terdaftar pada poliklinik ini.', 422);
            }

            Polyclinic::softDeleteData($id);
            return $this->successResponse(null, 'Poliklinik berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus, data poliklinik tidak ditemukan', 404);
        }
    }

    public function restore($id) {
        try {
            Polyclinic::restoreData($id);
            return $this->successResponse(null, 'Data poliklinik berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan di tempat sampah', 404);
        }
    }
}