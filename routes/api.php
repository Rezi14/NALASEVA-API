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
use App\Http\Controllers\Api\PuskesmasProfileController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\SettingController;

// --- Public Routes ---
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('auth/forgot-password/otp', [AuthController::class, 'requestPasswordResetOtp'])->middleware('throttle:auth');
Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
Route::get('puskesmas-profile', [PuskesmasProfileController::class, 'show']);
Route::get('payments/{id}/proof-image', [PaymentController::class, 'getProofImage']);

// --- Protected Routes ---
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth actions (All Roles)
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/profile', [AuthController::class, 'profile']);
    Route::match(['post', 'put', 'patch'], 'auth/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/fcm-token', [AuthController::class, 'updateFcmToken']);

    // Dashboard Stats
    Route::get('dashboard-stats', [DashboardController::class, 'getStats'])->middleware('role:admin,doctor');

    // --- Admin-Only Sensitive Routes ---
    Route::middleware('role:admin')->group(function () {
        // Puskesmas Profile Management
        Route::put('puskesmas-profile', [PuskesmasProfileController::class, 'update']);

        // Queue Admin Operations (Check-In & Skip)
        Route::post('queues/{id}/checkin', [QueueController::class, 'checkIn']);
        Route::post('queues/{id}/skip', [QueueController::class, 'skip']);
        Route::post('queues/{id}/recall', [QueueController::class, 'recall']);

        // Centralized Admin-Only CRUD / Resource Mutators
        Route::apiResource('users', UserController::class);
        Route::post('users/{id}/restore', [UserController::class, 'restore']);

        Route::post('polyclinics', [PolyclinicController::class, 'store']);
        Route::put('polyclinics/{id}', [PolyclinicController::class, 'update']);
        Route::patch('polyclinics/{id}', [PolyclinicController::class, 'update']);
        Route::delete('polyclinics/{id}', [PolyclinicController::class, 'destroy']);
        Route::post('polyclinics/{id}/restore', [PolyclinicController::class, 'restore']);

        Route::post('doctors', [DoctorController::class, 'store']);
        Route::put('doctors/{id}', [DoctorController::class, 'update']);
        Route::patch('doctors/{id}', [DoctorController::class, 'update']);
        Route::delete('doctors/{id}', [DoctorController::class, 'destroy']);
        Route::post('doctors/{id}/restore', [DoctorController::class, 'restore']);

        Route::post('doctor-schedules', [DoctorScheduleController::class, 'store']);
        Route::put('doctor-schedules/{id}', [DoctorScheduleController::class, 'update']);
        Route::patch('doctor-schedules/{id}', [DoctorScheduleController::class, 'update']);
        Route::delete('doctor-schedules/{id}', [DoctorScheduleController::class, 'destroy']);
        Route::post('doctor-schedules/{id}/restore', [DoctorScheduleController::class, 'restore']);

        Route::post('patients', [PatientController::class, 'store']);
        Route::delete('patients/{id}', [PatientController::class, 'destroy']);
        Route::post('patients/{id}/restore', [PatientController::class, 'restore']);

        Route::post('clinic-holidays', [ClinicHolidayController::class, 'store']);
        Route::put('clinic-holidays/{id}', [ClinicHolidayController::class, 'update']);
        Route::patch('clinic-holidays/{id}', [ClinicHolidayController::class, 'update']);
        Route::delete('clinic-holidays/{id}', [ClinicHolidayController::class, 'destroy']);

        Route::post('doctor-leaves', [DoctorLeaveController::class, 'store']);
        Route::put('doctor-leaves/{id}', [DoctorLeaveController::class, 'update']);
        Route::patch('doctor-leaves/{id}', [DoctorLeaveController::class, 'update']);
        Route::delete('doctor-leaves/{id}', [DoctorLeaveController::class, 'destroy']);

        Route::post('queues/{id}/restore', [QueueController::class, 'restore']);
        Route::post('examinations/{id}/restore', [ExaminationController::class, 'restore']);

        // Admin Payment Verification
        Route::post('payments/{id}/verify', [PaymentController::class, 'verify']);
        Route::post('payments/{id}/cash-pay', [PaymentController::class, 'cashPay']);

        // Admin Settings
        Route::get('settings', [SettingController::class, 'index']);
        Route::put('settings', [SettingController::class, 'update']);
    });

    // --- Doctor & Admin Authorized Mutators ---
    Route::middleware('role:admin,doctor')->group(function () {
        // Queue status mutation
        Route::put('queues/{id}', [QueueController::class, 'update']);
        Route::patch('queues/{id}', [QueueController::class, 'update']);
        
        // Examination CRUD
        Route::post('examinations', [ExaminationController::class, 'store']);
        Route::put('examinations/{id}', [ExaminationController::class, 'update']);
        Route::patch('examinations/{id}', [ExaminationController::class, 'update']);
        Route::delete('examinations/{id}', [ExaminationController::class, 'destroy']);
    });

    // --- Doctor-Only Dedicated Features ---
    Route::middleware('role:doctor')->group(function () {
        Route::patch('doctors/me/status', [DoctorController::class, 'updateStatus']);
    });

    // --- Shared Open Features (Read/Dynamic internal ownership filters) ---
    Route::get('polyclinics', [PolyclinicController::class, 'index']);
    Route::get('polyclinics/{id}', [PolyclinicController::class, 'show']);
    
    Route::get('doctors', [DoctorController::class, 'index']);
    Route::get('doctors/{id}', [DoctorController::class, 'show']);
    
    Route::get('doctor-schedules', [DoctorScheduleController::class, 'index']);
    Route::get('doctor-schedules/{id}', [DoctorScheduleController::class, 'show']);
    
    Route::get('clinic-holidays', [ClinicHolidayController::class, 'index']);
    Route::get('clinic-holidays/{id}', [ClinicHolidayController::class, 'show']);
    
    Route::get('doctor-leaves', [DoctorLeaveController::class, 'index']);
    Route::get('doctor-leaves/{id}', [DoctorLeaveController::class, 'show']);

    Route::get('patients', [PatientController::class, 'index']);
    Route::get('patients/{id}', [PatientController::class, 'show']);
    Route::put('patients/{id}', [PatientController::class, 'update']);
    Route::patch('patients/{id}', [PatientController::class, 'update']);

    // Queues and Examinations index/show (Internal IDOR handled at Controller layer)
    Route::get('queues', [QueueController::class, 'index']);
    Route::get('queues/{id}', [QueueController::class, 'show']);
    Route::post('queues', [QueueController::class, 'store'])->middleware('throttle:5,1'); // Booking
    Route::delete('queues/{id}', [QueueController::class, 'destroy']); // Cancellation
    
    Route::get('examinations', [ExaminationController::class, 'index']);
    Route::get('examinations/{id}', [ExaminationController::class, 'show']);

    // Payments (Shared with ownership checks)
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{id}', [PaymentController::class, 'show']);
    Route::post('payments/{id}/upload-proof', [PaymentController::class, 'uploadProof'])->middleware('throttle:5,1');

    // Medicines (Shared read-only)
    Route::get('medicines', [MedicineController::class, 'index']);
    Route::get('medicines/{id}', [MedicineController::class, 'show']);

    // Pharmacy & Medicine Management (Admin & Pharmacist)
    Route::middleware('role:admin,pharmacist')->group(function () {
        Route::get('pharmacy/queues', [PharmacyController::class, 'index']);
        Route::post('pharmacy/queues/{id}/dispense', [PharmacyController::class, 'dispense']);
        Route::post('pharmacy/queues/{id}/call', [PharmacyController::class, 'callPatient']);
        
        // Medicines CRUD
        Route::post('medicines', [MedicineController::class, 'store']);
        Route::put('medicines/{id}', [MedicineController::class, 'update']);
        Route::patch('medicines/{id}', [MedicineController::class, 'update']);
        Route::delete('medicines/{id}', [MedicineController::class, 'destroy']);
        Route::post('medicines/{id}/restore', [MedicineController::class, 'restore']);
    });
});
