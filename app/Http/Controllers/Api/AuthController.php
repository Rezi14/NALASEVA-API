<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Patient;
use App\Traits\ApiResponse;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Email atau password salah', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Load relations
        $user->load(['patient', 'doctor']);

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->whereNull('deleted_at')],
            'password' => 'required|string|min:8',
            'national_id' => ['required', 'digits:16', Rule::unique('users')->whereNull('deleted_at')],
            'phone_number' => 'required|string|max:20',
            'gender' => 'required|string|in:Laki-laki,Perempuan',
            'birth_date' => 'required|date_format:Y-m-d',
            'address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'patient',
                    'phone' => $request->phone_number,
                    'address' => $request->address,
                    'national_id' => $request->national_id,
                    'gender' => $request->gender,
                    'birth_date' => $request->birth_date,
                ]);

                Patient::create([
                    'user_id' => $user->id,
                ]);

                $token = $user->createToken('auth_token')->plainTextToken;
                $user->load('patient');

                return response()->json([
                    'status' => 'success',
                    'message' => 'Registrasi berhasil',
                    'data' => [
                        'user' => $user,
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                    ]
                ], 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat registrasi: ' . $e->getMessage(), 500);
        }
    }

    public function requestPasswordResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'national_id' => 'required|digits:16',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)
                    ->where('national_id', $request->national_id)
                    ->first();

        if (!$user) {
            return $this->errorResponse('Data NIK atau email tidak cocok / tidak ditemukan', 404);
        }

        // Hapus OTP lama milik email ini sebelum mengenerate yang baru
        DB::table('password_reset_otps')->where('email', $request->email)->delete();

        $otp = sprintf('%06d', rand(100000, 999999));

        DB::table('password_reset_otps')->insert([
            'email' => $request->email,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Return OTP for easy demo/testing purposes
        return response()->json([
            'status' => 'success',
            'message' => 'Kode OTP verifikasi berhasil dikirim ke email Anda.',
            'data' => [
                'otp_code_testing' => $otp
            ]
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'national_id' => 'required|digits:16',
            'otp_code' => 'required|string|size:6',
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Validasi OTP (BL-04: Menguji kecocokan dengan seluruh OTP aktif milik email tersebut)
        $validOtp = DB::table('password_reset_otps')
            ->where('email', $request->email)
            ->where('otp_code', $request->otp_code)
            ->where('expires_at', '>', now())
            ->exists();

        if (!$validOtp) {
            return $this->errorResponse('Kode OTP salah atau telah kedaluwarsa.', 422);
        }

        $user = User::where('email', $request->email)
                    ->where('national_id', $request->national_id)
                    ->first();

        if (!$user) {
            return $this->errorResponse('Data NIK atau email tidak cocok / tidak ditemukan', 404);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        // Hapus OTP yang sudah terpakai
        DB::table('password_reset_otps')->where('email', $request->email)->delete();

        // Revoke all active sessions/tokens to secure the account
        $user->tokens()->delete();

        return $this->successResponse(null, 'Password berhasil diperbarui, silakan login kembali');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout berhasil');
    }

    public function updateFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        User::updateData($request->user()->id, ['fcm_token' => $request->fcm_token]);

        return $this->successResponse(null, 'FCM Token berhasil diperbarui');
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load(['patient', 'doctor']);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diambil',
            'data' => $user
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string',
            'national_id' => ['sometimes', 'required', 'digits:16', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'gender' => 'sometimes|required|string|in:Laki-laki,Perempuan',
            'birth_date' => 'sometimes|required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            return DB::transaction(function () use ($request, $user) {
                // Update User fields
                $userData = $request->only(['name', 'email', 'phone', 'address', 'gender', 'birth_date']);

                // Allow updating national_id only if it is currently null/empty
                if ($request->has('national_id') && empty($user->national_id)) {
                    $userData['national_id'] = $request->national_id;
                }

                $user->update($userData);

                $user->load(['patient', 'doctor']);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Profil berhasil diperbarui',
                    'data' => $user
                ]);
            });
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat memperbarui profil: ' . $e->getMessage(), 500);
        }
    }
}