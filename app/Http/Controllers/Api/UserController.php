<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Exception;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request) {
        if ($request->has('page') || $request->has('per_page') || $request->has('paginate')) {
            $limit = $request->input('per_page', 20);
            $paginated = User::paginate($limit);
            return $this->successResponse(UserResource::collection($paginated), 'Daftar user berhasil diambil');
        }
        return $this->successResponse(UserResource::collection(User::getAll()), 'Daftar user berhasil diambil');
    }

    public function store(StoreUserRequest $request) {
        $data = User::storeData($request->validated());
        return $this->successResponse(new UserResource($data), 'User berhasil ditambahkan', 201);
    }

    public function show($id) {
        try {
            $user = User::getById($id);
            return $this->successResponse(new UserResource($user), 'Detail user ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data user tidak ditemukan', 404);
        }
    }

    public function update(UpdateUserRequest $request, $id) {
        try {
            // Restriction: User can only update themselves
            if ($request->user()->id != $id) {
                return $this->errorResponse('Akses ditolak. Anda tidak memiliki otoritas untuk mengubah data user lain.', 403);
            }

            $data = User::updateData($id, $request->validated());
            return $this->successResponse(new UserResource($data), 'Data user berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data user tidak ditemukan', 404);
        }
    }

    public function destroy(Request $request, $id) {
        try {
            // Mencegah admin menghapus dirinya sendiri
            if ($request->user()->id == $id) {
                return $this->errorResponse('Akses ditolak. Anda tidak diperkenankan menghapus akun Anda sendiri.', 403);
            }

            $targetUser = User::findOrFail($id);

            // Mencegah admin menghapus akun admin lain
            if ($targetUser->role === 'admin') {
                return $this->errorResponse('Akses ditolak. Anda tidak diperkenankan menghapus akun administrator lain.', 403);
            }

            $targetUser->delete(); // This triggers soft delete and the booted() cascade events
            return $this->successResponse(null, 'User berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus, data user tidak ditemukan', 404);
        }
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