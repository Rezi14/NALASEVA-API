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
            $patient = Patient::where('user_id', $user->id)->with('user')->get();
            return $this->successResponse($patient, 'Data profil Anda berhasil diambil');
        }
        return $this->successResponse(Patient::with('user')->get(), 'Daftar pasien berhasil diambil');
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:6',
            'national_id'    => ['required', 'digits:16', Rule::unique('users')->whereNull('deleted_at')],
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
                    'national_id' => $request->national_id,
                    'gender' => $request->gender,
                    'birth_date' => $request->birth_date,
                ]);

                // 2. Create Patient
                $patient = Patient::create([
                    'user_id' => $user->id,
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
                'name'                  => 'sometimes|required|string|max:255',
                'full_name'             => 'sometimes|required|string|max:255',
                'phone'                 => 'sometimes|nullable|string|max:20',
                'phone_number'          => 'sometimes|nullable|string|max:20',
                'gender'                => 'sometimes|required|string|in:Laki-laki,Perempuan',
                'birth_date'            => 'sometimes|required|date_format:Y-m-d',
                'national_id'           => ['sometimes', 'required', 'digits:16', Rule::unique('users')->ignore($patient->user_id)->whereNull('deleted_at')],
                'email'                 => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($patient->user_id)->whereNull('deleted_at')],
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(implode(', ', $validator->errors()->all()), 422);
            }

            $validatedData = $validator->validated();

            return \Illuminate\Support\Facades\DB::transaction(function () use ($patient, $validatedData) {
                // Update tabel Users jika ada perubahan profil
                $userData = [];
                if (isset($validatedData['name'])) {
                    $userData['name'] = $validatedData['name'];
                } elseif (isset($validatedData['full_name'])) {
                    $userData['name'] = $validatedData['full_name'];
                }
                
                if (isset($validatedData['email'])) {
                    $userData['email'] = $validatedData['email'];
                }
                
                if (isset($validatedData['phone'])) {
                    $userData['phone'] = $validatedData['phone'];
                } elseif (isset($validatedData['phone_number'])) {
                    $userData['phone'] = $validatedData['phone_number'];
                }
                
                if (isset($validatedData['gender'])) {
                    $userData['gender'] = $validatedData['gender'];
                }
                
                if (isset($validatedData['birth_date'])) {
                    $userData['birth_date'] = $validatedData['birth_date'];
                }
                
                if (isset($validatedData['national_id'])) {
                    $userData['national_id'] = $validatedData['national_id'];
                }

                if (!empty($userData)) {
                    $patient->user()->update($userData);
                }

                return $this->successResponse($patient->load('user'), 'Profil berhasil diperbarui');
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