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
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:6',
            'national_id'    => ['required', 'digits:16', Rule::unique('patients')->whereNull('deleted_at')],
            'gender'         => 'required|string|in:Laki-laki,Perempuan',
            'birth_date'     => 'required|date_format:Y-m-d',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            return \DB::transaction(function() use ($request) {
                // 1. Create User
                $user = \App\Models\User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => \Hash::make($request->password),
                    'role' => 'patient',
                    'phone' => $request->phone,
                    'address' => $request->address,
                ]);

                // 2. Create Patient
                $patient = Patient::create([
                    'user_id' => $user->id,
                    'national_id' => $request->national_id,
                    'gender' => $request->gender,
                    'birth_date' => $request->birth_date,
                    // Use name as full_name for backward compatibility if needed by the model
                    'full_name' => $request->name,
                    'phone_number' => $request->phone ?? '-',
                ]);

                return $this->successResponse($patient->load('user'), 'Pasien berhasil didaftarkan', 201);
            });
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menambahkan pasien: ' . $e->getMessage(), 500);
        }
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
                'full_name'             => 'sometimes|required|string|max:255',
                'phone_number'          => 'sometimes|required|string|max:20',
                'gender'                => 'sometimes|required|string|in:Laki-laki,Perempuan',
                'birth_date'            => 'sometimes|required|date_format:Y-m-d',
                'national_id'           => ['sometimes', 'required', 'digits:16', Rule::unique('patients')->ignore($id)->whereNull('deleted_at')],
                // Memungkinkan update email juga melalui relasi user
                'email'                 => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($patient->user_id)->whereNull('deleted_at')],
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(implode(', ', $validator->errors()->all()), 422);
            }

            $validatedData = $validator->validated();

            return \Illuminate\Support\Facades\DB::transaction(function () use ($patient, $validatedData) {
                // 1. Update tabel Patients
                $patient->update($validatedData);

                // 2. Update tabel Users jika ada perubahan Nama atau Email
                $userData = [];
                if (isset($validatedData['full_name'])) {
                    $userData['name'] = $validatedData['full_name'];
                }
                if (isset($validatedData['email'])) {
                    $userData['email'] = $validatedData['email'];
                }

                if (!empty($userData)) {
                    $patient->user()->update($userData);
                }

                return $this->successResponse($patient->load('user'), 'Profil berhasil diperbarui di kedua tabel');
            });
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui: ' . $e->getMessage(), 404);
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