<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class DoctorController extends Controller
{
    use ApiResponse;

    public function index() {
        return $this->successResponse(Doctor::getAll(), 'Daftar dokter berhasil diambil');
    }

    public function myProfile(Request $request) {
        $doctor = Doctor::where('user_id', $request->user()->id)->first();
        if (!$doctor) {
            return $this->errorResponse('Data profil dokter tidak ditemukan', 404);
        }
        return $this->successResponse(Doctor::getById($doctor->id), 'Profil dokter berhasil diambil');
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:6',
            'polyclinic_id'  => 'required|integer|exists:polyclinics,id',
            'specialization' => 'required|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'national_id'    => 'required|string|digits:16|unique:users,national_id',
            'gender'         => 'nullable|string|in:Laki-laki,Perempuan',
            'birth_date'     => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            return \DB::transaction(function() use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => \Hash::make($request->password),
                    'role' => 'doctor',
                    'national_id' => $request->national_id,
                    'gender' => $request->gender,
                    'birth_date' => $request->birth_date,
                ]);

                $doctor = Doctor::create([
                    'user_id' => $user->id,
                    'polyclinic_id' => $request->polyclinic_id,
                    'specialization' => $request->specialization,
                    'license_number' => $request->license_number,
                ]);

                return $this->successResponse(Doctor::getById($doctor->id), 'Dokter berhasil ditambahkan', 201);
            });
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menambahkan dokter: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id) {
        try {
            $doctor = Doctor::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'name'           => 'sometimes|required|string|max:255',
                'polyclinic_id'  => 'sometimes|required|integer|exists:polyclinics,id',
                'specialization' => 'sometimes|required|string|max:255',
                'license_number' => 'sometimes|nullable|string|max:255',
                'national_id'    => ['sometimes', 'required', 'string', 'digits:16', Rule::unique('users')->ignore($doctor->user_id)->whereNull('deleted_at')],
                'gender'         => 'sometimes|nullable|string|in:Laki-laki,Perempuan',
                'birth_date'     => 'sometimes|nullable|date_format:Y-m-d',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $data = $validator->validated();

            return \DB::transaction(function() use ($data, $doctor) {
                // Update User fields
                $userData = collect($data)->only(['name', 'national_id', 'gender', 'birth_date'])->toArray();
                if (!empty($userData)) {
                    $doctor->user->update($userData);
                }
                
                // Update Doctor specific fields
                $doctorData = collect($data)->only(['polyclinic_id', 'specialization', 'license_number'])->toArray();
                if (!empty($doctorData)) {
                    $doctor->update($doctorData);
                }
                
                return $this->successResponse(Doctor::getById($doctor->id), 'Data dokter berhasil diperbarui');
            });
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data dokter tidak ditemukan', 404);
        }
    }

    public function destroy($id) {
        try {
            $doctor = Doctor::findOrFail($id);
            $userId = $doctor->user_id;
            
            // Hapus dokter sekaligus user login-nya
            $doctor->delete();
            User::where('id', $userId)->delete();
            
            return $this->successResponse(null, 'Dokter dan akun berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus, data dokter tidak ditemukan', 404);
        }
    }

    public function restore($id) {
        try {
            $doctor = Doctor::onlyTrashed()->findOrFail($id);
            $doctor->restore();
            User::onlyTrashed()->where('id', $doctor->user_id)->restore();
            
            return $this->successResponse(null, 'Data dokter berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan', 404);
        }
    }
}