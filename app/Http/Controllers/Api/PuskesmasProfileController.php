<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PuskesmasProfileService;
use App\Http\Requests\UpdatePuskesmasProfileRequest;
use App\Http\Resources\PuskesmasProfileResource;
use App\Traits\ApiResponse;
use Exception;

class PuskesmasProfileController extends Controller
{
    use ApiResponse;

    protected PuskesmasProfileService $profileService;

    public function __construct(PuskesmasProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function show()
    {
        try {
            $profile = $this->profileService->getProfile();
            return $this->successResponse(new PuskesmasProfileResource($profile), 'Detail profil Puskesmas berhasil diambil');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengambil profil Puskesmas: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdatePuskesmasProfileRequest $request)
    {
        try {
            $profile = $this->profileService->updateProfile($request->validated());
            return $this->successResponse(new PuskesmasProfileResource($profile), 'Profil Puskesmas berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui profil Puskesmas: ' . $e->getMessage(), 500);
        }
    }
}
