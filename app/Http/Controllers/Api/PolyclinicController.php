<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Polyclinic;
use App\Traits\ApiResponse;
use App\Http\Requests\StorePolyclinicRequest;
use App\Http\Requests\UpdatePolyclinicRequest;
use App\Http\Resources\PolyclinicResource;
use Illuminate\Http\Request;
use Exception;

class PolyclinicController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->successResponse(PolyclinicResource::collection(Polyclinic::getAll()), 'Daftar poliklinik berhasil diambil');
    }

    public function store(StorePolyclinicRequest $request)
    {
        $data = Polyclinic::storeData($request->validated());
        return $this->successResponse(new PolyclinicResource($data), 'Poliklinik berhasil ditambahkan', 201);
    }

    public function show($id)
    {
        try {
            $data = Polyclinic::getById($id);
            return $this->successResponse(new PolyclinicResource($data), 'Detail poliklinik ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data poliklinik tidak ditemukan', 404);
        }
    }

    public function update(UpdatePolyclinicRequest $request, $id)
    {
        try {
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

            $data = Polyclinic::updateData($id, $request->validated());
            return $this->successResponse(new PolyclinicResource($data), 'Poliklinik berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data poliklinik tidak ditemukan', 404);
        }
    }

    public function destroy($id)
    {
        try {
            $hasActiveQueues = \App\Models\Queue::where('polyclinic_id', $id)
                                                ->whereIn('status', ['booked', 'waiting', 'examining'])
                                                ->exists();
            if ($hasActiveQueues) {
                return $this->errorResponse('Gagal menghapus poliklinik. Masih ada antrean aktif pada poliklinik ini.', 422);
            }

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

    public function restore($id)
    {
        try {
            Polyclinic::restoreData($id);
            return $this->successResponse(null, 'Data poliklinik berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan di tempat sampah', 404);
        }
    }
}