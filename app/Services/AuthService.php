<?php

namespace App\Services;

use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Exception;

class AuthService
{
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new Exception('Email atau password salah', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load(['patient', 'doctor']);

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'patient',
                'phone' => $data['phone_number'],
                'address' => $data['address'],
                'national_id' => $data['national_id'],
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'],
            ]);

            Patient::create([
                'user_id' => $user->id,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;
            $user->load('patient');

            return [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ];
        });
    }

    public function requestPasswordResetOtp(array $data): string
    {
        $user = User::where('email', $data['email'])
                    ->where('national_id', $data['national_id'])
                    ->first();

        if (!$user) {
            throw new Exception('Data NIK atau email tidak cocok / tidak ditemukan', 404);
        }

        DB::table('password_reset_otps')->where('email', $data['email'])->delete();

        $otp = sprintf('%06d', rand(100000, 999999));

        DB::table('password_reset_otps')->insert([
            'email' => $data['email'],
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $otp;
    }

    public function forgotPassword(array $data): void
    {
        $validOtp = DB::table('password_reset_otps')
            ->where('email', $data['email'])
            ->where('otp_code', $data['otp_code'])
            ->where('expires_at', '>', now())
            ->exists();

        if (!$validOtp) {
            throw new Exception('Kode OTP salah atau telah kedaluwarsa.', 422);
        }

        $user = User::where('email', $data['email'])
                    ->where('national_id', $data['national_id'])
                    ->first();

        if (!$user) {
            throw new Exception('Data NIK atau email tidak cocok / tidak ditemukan', 404);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        DB::table('password_reset_otps')->where('email', $data['email'])->delete();
        $user->tokens()->delete();
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function updateFcmToken(User $user, string $fcmToken): void
    {
        $user->update(['fcm_token' => $fcmToken]);
    }

    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $userData = collect($data)->only(['name', 'email', 'phone', 'address', 'gender', 'birth_date'])->toArray();

            if (isset($data['national_id']) && empty($user->national_id)) {
                $userData['national_id'] = $data['national_id'];
            }

            $user->update($userData);
            $user->load(['patient', 'doctor']);

            return $user;
        });
    }
}
