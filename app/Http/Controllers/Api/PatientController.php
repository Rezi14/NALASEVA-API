<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class PatientController extends Controller
{
    use ApiResponse;

    public function index(Request $request) {
        $user = $request->user();
        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->get();
            return $this->successResponse($patient, 'Data profil Anda berhasil diambil');
        }
        return $this->successResponse(Patient::getAll(), 'Daftar pasien berhasil diambil');
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id'               => 'required|integer|exists:users,id',
            'medical_record_number' => ['sometimes', 'nullable', 'string', Rule::unique('patients')->whereNull('deleted_at')],
            'national_id'           => ['required', 'digits:16', Rule::unique('patients')->whereNull('deleted_at')],
            'full_name'             => 'required|string|max:255',
            'phone_number'          => 'required|string|max:20'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(implode(', ', $validator->errors()->all()), 422);
        }

        $data = Patient::storeData($validator->validated());
        return $this->successResponse($data, 'Pasien berhasil didaftarkan', 201);
    }

    public function show(Request $request, $id) {
        try {
            $patient = Patient::getById($id);
            $user = $request->user();
            
            if ($user->role === 'patient' && $patient->user_id !== $user->id) {
                return $this->errorResponse('Akses ditolak. Anda hanya dapat melihat profil Anda sendiri.', 403);
            }
            
            return $this->successResponse($patient, 'Detail pasien ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data pasien tidak ditemukan', 404);
        }
    }

    public function update(Request $request, $id) {
        try {
            $patient = Patient::findOrFail($id);
            $user = $request->user();
            
            // Mencegah Celah Keamanan IDOR: Pasien tidak boleh mengubah profil orang lain
            if ($user->role === 'patient' && $patient->user_id !== $user->id) {
                return $this->errorResponse('Akses ditolak. Anda hanya dapat memperbarui profil Anda sendiri.', 403);
            }

            $validator = Validator::make($request->all(), [
                'user_id'               => 'sometimes|required|integer|exists:users,id',
                'medical_record_number' => ['sometimes', 'required', 'string', Rule::unique('patients')->ignore($id)->whereNull('deleted_at')],
                'national_id'           => ['sometimes', 'required', 'digits:16', Rule::unique('patients')->ignore($id)->whereNull('deleted_at')],
                'full_name'             => 'sometimes|required|string|max:255',
                'phone_number'          => 'sometimes|required|string|max:20'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(implode(', ', $validator->errors()->all()), 422);
            }

            // Perbaikan Mass Assignment: Menggunakan validated() bukan request->all()
            $patient->update($validator->validated());
            return $this->successResponse($patient, 'Data pasien berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data pasien tidak ditemukan', 404);
        }
    }

    public function destroy($id) {
        try {
            Patient::softDeleteData($id);
            return $this->successResponse(null, 'Pasien berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus, data pasien tidak ditemukan', 404);
        }
    }

    public function restore($id) {
        try {
            Patient::restoreData($id);
            return $this->successResponse(null, 'Data pasien berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan di tempat sampah', 404);
        }
    }
}