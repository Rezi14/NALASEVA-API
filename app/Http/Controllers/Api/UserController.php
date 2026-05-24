<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class UserController extends Controller
{
    use ApiResponse;

    public function index() {
        return $this->successResponse(User::getAll(), 'Daftar user berhasil diambil');
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')],
            'password' => 'required|string|min:6',
            'role'     => 'required|string|in:doctor,patient', // Admin can only create Doctor or Patient
            'phone'       => 'required|string|max:20',
            'address'     => 'required|string',
            'national_id' => ['required', 'digits:16', Rule::unique('users')->whereNull('deleted_at')],
            'gender'      => 'required|string|in:Laki-laki,Perempuan',
            'birth_date'  => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $data = User::storeData($validator->validated());
        return $this->successResponse($data, 'User berhasil ditambahkan', 201);
    }

    public function show($id) {
        try {
            return $this->successResponse(User::getById($id), 'Detail user ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data user tidak ditemukan', 404);
        }
    }

    public function update(Request $request, $id) {
        try {
            // Restriction: User can only update themselves
            if ($request->user()->id != $id) {
                return $this->errorResponse('Akses ditolak. Anda tidak memiliki otoritas untuk mengubah data user lain.', 403);
            }

            $validator = Validator::make($request->all(), [
                'name'     => 'sometimes|required|string|max:255',
                'email'    => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($id)->whereNull('deleted_at')],
                'password' => 'sometimes|nullable|string|min:6',
                'phone'       => 'sometimes|required|string|max:20',
                'address'     => 'sometimes|required|string',
                'national_id' => ['sometimes', 'required', 'digits:16', Rule::unique('users')->ignore($id)->whereNull('deleted_at')],
                'gender'      => 'sometimes|required|string|in:Laki-laki,Perempuan',
                'birth_date'  => 'sometimes|required|date_format:Y-m-d',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $data = User::updateData($id, $validator->validated());
            return $this->successResponse($data, 'Data user berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data user tidak ditemukan', 404);
        }
    }

    public function destroy(Request $request, $id) {
        // Admin cannot delete users anymore based on new policy
        return $this->errorResponse('Akses ditolak. Kebijakan baru melarang penghapusan user oleh Admin.', 403);
    }

    public function restore($id) {
        try {
            User::restoreData($id);
            return $this->successResponse(null, 'Data user berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan di tempat sampah', 404);
        }
    }
}