<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PolyclinicController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\ExaminationController;
use App\Http\Controllers\Api\DoctorScheduleController;
use App\Http\Controllers\Api\ClinicHolidayController;
use App\Http\Controllers\Api\DoctorLeaveController;

// --- Public Routes ---
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);

// --- Protected Routes ---
Route::middleware('auth:sanctum')->group(function () {
    
    // Dashboard Stats
    Route::get('dashboard-stats', [DashboardController::class, 'getStats']);

    // Auth actions
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/profile', [AuthController::class, 'profile']);
    Route::post('auth/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/fcm-token', [AuthController::class, 'updateFcmToken']);

    // Custom API Mobile Features
    Route::get('doctors/profile', [DoctorController::class, 'myProfile']);
    Route::patch('doctors/me/status', [DoctorController::class, 'updateStatus']);
    Route::post('queues/{id}/checkin', [QueueController::class, 'checkIn']);
    Route::post('queues/{id}/skip', [QueueController::class, 'skip']);

    // Rute khusus untuk Restore Data
    Route::post('users/{id}/restore', [UserController::class, 'restore']);
    Route::post('polyclinics/{id}/restore', [PolyclinicController::class, 'restore']);
    Route::post('doctors/{id}/restore', [DoctorController::class, 'restore']);
    Route::post('doctor-schedules/{id}/restore', [DoctorScheduleController::class, 'restore']);
    Route::post('patients/{id}/restore', [PatientController::class, 'restore']);
    Route::post('queues/{id}/restore', [QueueController::class, 'restore']);
    Route::post('examinations/{id}/restore', [ExaminationController::class, 'restore']);

    // Rute CRUD
    Route::apiResource('users', UserController::class);
    Route::apiResource('polyclinics', PolyclinicController::class);
    Route::apiResource('doctors', DoctorController::class);
    Route::apiResource('doctor-schedules', DoctorScheduleController::class);
    Route::apiResource('patients', PatientController::class);
    Route::apiResource('queues', QueueController::class);
    Route::apiResource('examinations', ExaminationController::class);
    Route::apiResource('clinic-holidays', ClinicHolidayController::class);
    Route::apiResource('doctor-leaves', DoctorLeaveController::class);
});
