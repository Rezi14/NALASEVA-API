<?php

namespace App\Services;

use App\Models\PuskesmasProfile;

class PuskesmasProfileService
{
    public function getProfile(): PuskesmasProfile
    {
        return PuskesmasProfile::getProfile();
    }

    public function updateProfile(array $data): PuskesmasProfile
    {
        return PuskesmasProfile::updateProfile($data);
    }
}
