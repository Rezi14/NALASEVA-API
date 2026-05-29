<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuskesmasProfile extends Model
{
    protected $fillable = ['name', 'address', 'phone', 'email', 'logo_url', 'latitude', 'longitude'];

    public static function getProfile()
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Puskesmas Sehat Utama',
                'address' => 'Jl. Raya Sehat No. 12, Jakarta',
                'phone' => '021-1234567',
                'email' => 'info@puskesmassehat.go.id',
                'latitude' => -6.175392,
                'longitude' => 106.827153,
            ]
        );
    }

    public static function updateProfile($data)
    {
        $profile = self::getProfile();
        $profile->update($data);
        return $profile;
    }
}
