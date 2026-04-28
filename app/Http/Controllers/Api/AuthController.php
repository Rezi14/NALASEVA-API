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
            'national_id' => ['required', 'digits:16', Rule::unique('patients')->whereNull('deleted_at')],
            'phone_number' => 'required|string|max:20',
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
                ]);

                $mrn = 'NS-' . date('Ymd') . '-' . $user->id;

                Patient::create([
                    'user_id' => $user->id,
                    'medical_record_number' => $mrn,
                    'national_id' => $request->national_id,
                    'full_name' => $request->name,
                    'phone_number' => $request->phone_number,
                ]);

                $token = $user->createToken('auth_token')->plainTextToken;

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

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'national_id' => 'required|digits:16',
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('User tidak ditemukan', 404);
        }

        $patient = Patient::where('user_id', $user->id)
                         ->where('national_id', $request->national_id)
                         ->first();

        if (!$patient) {
            return $this->errorResponse('Data NIK tidak cocok dengan email yang terdaftar', 403);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

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
}