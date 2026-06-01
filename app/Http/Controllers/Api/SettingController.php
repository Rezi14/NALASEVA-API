<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Exception;

class SettingController extends Controller
{
    use ApiResponse;

    /**
     * Get all settings
     */
    public function index()
    {
        try {
            $settings = Setting::all()->pluck('value', 'key');
            
            // Set defaults if empty
            $defaults = [
                'registration_fee' => $settings->get('registration_fee', '10000'),
                'slot_duration_minutes' => $settings->get('slot_duration_minutes', '15'),
            ];

            return $this->successResponse($defaults, 'Pengaturan puskesmas berhasil diambil');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengambil pengaturan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'registration_fee' => 'sometimes|required|numeric|min:0',
            'slot_duration_minutes' => 'sometimes|required|integer|min:1|max:120',
        ]);

        try {
            foreach ($validated as $key => $value) {
                Setting::setValue($key, (string)$value);
            }

            $settings = Setting::all()->pluck('value', 'key');
            $updated = [
                'registration_fee' => $settings->get('registration_fee', '10000'),
                'slot_duration_minutes' => $settings->get('slot_duration_minutes', '15'),
            ];

            return $this->successResponse($updated, 'Pengaturan puskesmas berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui pengaturan: ' . $e->getMessage(), 500);
        }
    }
}
