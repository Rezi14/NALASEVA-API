<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use App\Services\DoctorService;
use App\Traits\ApiResponse;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class DoctorController extends Controller
{
    use ApiResponse;

    protected DoctorService $doctorService;

    public function __construct(DoctorService $doctorService)
    {
        $this->doctorService = $doctorService;
    }

    public function index()
    {
        $doctors = Doctor::with(['user', 'polyclinic'])->get();
        return $this->successResponse(DoctorResource::collection($doctors), 'Daftar dokter berhasil diambil');
    }

    public function myProfile(Request $request)
    {
        $doctor = Doctor::where('user_id', $request->user()->id)->first();
        if (!$doctor) {
            return $this->errorResponse('Data profil dokter tidak ditemukan', 404);
        }
        $doctor->load(['user', 'polyclinic']);
        return $this->successResponse(new DoctorResource($doctor), 'Profil dokter berhasil diambil');
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_online' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $doctor = Doctor::where('user_id', $request->user()->id)->first();
        if (!$doctor) {
            return $this->errorResponse('Data dokter tidak ditemukan', 404);
        }

        $this->doctorService->updateStatus($doctor, $request->is_online);

        if (!$request->is_online) {
            $admins = User::where('role', 'admin')->whereNotNull('fcm_token')->get();
            if ($admins->isNotEmpty()) {
                try {
                    $firebaseService = new \App\Services\FirebaseNotificationService();
                    $title = "Dokter Sedang Istirahat";
                    $body = "Dokter " . $request->user()->name . " sedang beristirahat. Mohon jangan panggil antrean untuk sementara.";
                    foreach ($admins as $admin) {
                        $firebaseService->sendToToken($admin->fcm_token, $title, $body, [
                            'type' => 'doctor_status',
                            'doctor_id' => $doctor->id
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('FCM Doctor Status Notification Error: ' . $e->getMessage(), [
                        'doctor_id' => $doctor->id,
                        'exception' => $e
                    ]);
                }
            }
        }

        $doctor->load(['user', 'polyclinic']);
        return $this->successResponse(new DoctorResource($doctor), 'Status berhasil diperbarui');
    }

    public function store(StoreDoctorRequest $request)
    {
        try {
            $doctor = $this->doctorService->storeDoctor($request->validated());
            $doctor->load(['user', 'polyclinic']);
            return $this->successResponse(new DoctorResource($doctor), 'Dokter berhasil ditambahkan', 201);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menambahkan dokter: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateDoctorRequest $request, $id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $updatedDoctor = $this->doctorService->updateDoctor($doctor, $request->validated());
            $updatedDoctor->load(['user', 'polyclinic']);
            return $this->successResponse(new DoctorResource($updatedDoctor), 'Data dokter berhasil diperbarui');
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 422 ? 422 : 404;
            return $this->errorResponse($e->getMessage() ?: 'Gagal memperbarui, data dokter tidak ditemukan', $statusCode);
        }
    }

    public function destroy($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $this->doctorService->deleteDoctor($doctor);
            return $this->successResponse(null, 'Dokter dan akun berhasil dihapus');
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 422 ? 422 : 404;
            return $this->errorResponse($e->getMessage() ?: 'Gagal menghapus, data dokter tidak ditemukan', $statusCode);
        }
    }

    public function restore($id)
    {
        try {
            $this->doctorService->restoreDoctor($id);
            return $this->successResponse(null, 'Data dokter berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan', 404);
        }
    }
}