<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $data = $this->authService->login($validator->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'data' => [
                    'user' => new UserResource($data['user']),
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                ]
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
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
            $data = $this->authService->register($validator->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi berhasil',
                'data' => [
                    'user' => new UserResource($data['user']),
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                ]
            ], 201);
        } catch (Exception $e) {
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

        try {
            $otp = $this->authService->requestPasswordResetOtp($validator->validated());
            $responseData = [
                'status' => 'success',
                'message' => 'Kode OTP verifikasi berhasil dikirim ke email Anda.',
            ];
            
            // Only expose OTP code to response if NOT in production to prevent security bypass
            if (config('app.env') !== 'production') {
                $responseData['data'] = [
                    'otp_code_testing' => $otp
                ];
            }
            
            return response()->json($responseData, 200);
        } catch (Exception $e) {
            $statusCode = $e->getCode() === 404 ? 404 : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
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

        try {
            $this->authService->forgotPassword($validator->validated());
            return $this->successResponse(null, 'Password berhasil diperbarui, silakan login kembali');
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [404, 422]) ? $e->getCode() : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());
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

        $this->authService->updateFcmToken($request->user(), $request->fcm_token);
        return $this->successResponse(null, 'FCM Token berhasil diperbarui');
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load(['patient', 'doctor']);
        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diambil',
            'data' => new UserResource($user)
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
            $updatedUser = $this->authService->updateProfile($user, $validator->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Profil berhasil diperbarui',
                'data' => new UserResource($updatedUser)
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat memperbarui profil: ' . $e->getMessage(), 500);
        }
    }
}