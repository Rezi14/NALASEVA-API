<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PuskesmasProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PuskesmasProfileController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show()
    {
        try {
            $profile = PuskesmasProfile::getProfile();
            return response()->json([
                'success' => true,
                'message' => 'Detail profil Puskesmas berhasil diambil',
                'data' => $profile
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil profil Puskesmas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // Pastikan hanya admin yang bisa melakukan update
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Hanya Admin yang dapat memperbarui profil Puskesmas.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'logo_url' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $profile = PuskesmasProfile::updateProfile($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Profil Puskesmas berhasil diperbarui',
                'data' => $profile
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profil Puskesmas: ' . $e->getMessage()
            ], 500);
        }
    }
}
