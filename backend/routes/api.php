<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceExportController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyClosureController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorAreaController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::post('register', [UserController::class, 'store']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update']);
    Route::put('users/{user}/roles', [UserController::class, 'syncRoles']);
    Route::post('users/{user}/roles/{role}', [UserController::class, 'addRole']);
    Route::delete('users/{user}/roles/{role}', [UserController::class, 'removeRole']);
    Route::patch('users/{user}/status', [UserController::class, 'changeStatus']);
    Route::patch('users/{user}/password', [UserController::class, 'resetPassword']);
    Route::post('doctors', [DoctorController::class, 'registerDoctor']);
    Route::match(['put', 'patch'], 'doctors/{doctor}', [DoctorController::class, 'update']);
    Route::patch('doctors/{doctor}/status', [DoctorController::class, 'changeStatus']);
    Route::get('doctors/{doctor}/statistics', [DoctorController::class, 'statistics']);
    Route::patch('doctors/{doctor}/commission', [DoctorController::class, 'updateCommission']);
    Route::post('procedures', [ProcedureController::class, 'store']);
    Route::match(['put', 'patch'], 'procedures/{procedure}', [ProcedureController::class, 'update']);
    Route::patch('procedures/{procedure}/status', [ProcedureController::class, 'changeStatus']);
    Route::delete('procedures/{procedure}', [ProcedureController::class, 'destroy']);
    Route::match(['put', 'patch'], 'attendances/{attendance}', [AttendanceController::class, 'update']);
    Route::delete('attendances/{attendance}', [AttendanceController::class, 'destroy']);
    Route::patch('payments/{payment}/void', [PaymentController::class, 'void']);
    Route::patch('daily-closures/{dailyClosure}/reopen', [DailyClosureController::class, 'reopen']);
    Route::patch('patients/{patient}/status', [PatientController::class, 'changeStatus']);
    Route::get('patients/{patient}/history', [PatientController::class, 'history']);

});

Route::middleware(['auth:sanctum', 'role:admin,receptionist,doctor'])->group(function (): void {
    Route::get('dashboard', DashboardController::class);
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::patch('appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::get('doctors', [DoctorController::class, 'index']);
    Route::get('doctors/{doctor}', [DoctorController::class, 'show']);
    Route::get('procedures', [ProcedureController::class, 'index']);
    Route::get('procedures/{procedure}', [ProcedureController::class, 'show']);
    Route::get('attendances', [AttendanceController::class, 'index']);
    Route::get('attendances/{attendance}', [AttendanceController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:admin,receptionist'])->group(function (): void {
    Route::get('reports/attendances', AttendanceReportController::class);
    Route::get('reports/attendances/export/csv', [AttendanceExportController::class, 'csv']);
    Route::get('reports/attendances/export/pdf', [AttendanceExportController::class, 'pdf']);
    Route::get('patients', [PatientController::class, 'index']);
    Route::post('patients', [PatientController::class, 'store']);
    Route::get('patients/{patient}', [PatientController::class, 'show']);
    Route::match(['put', 'patch'], 'patients/{patient}', [PatientController::class, 'update']);
    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::post('attendances', [AttendanceController::class, 'store']);
    Route::get('attendances/{attendance}/payments', [PaymentController::class, 'index']);
    Route::post('attendances/{attendance}/payments', [PaymentController::class, 'store']);
    Route::get('daily-closures/{date}', [DailyClosureController::class, 'show']);
    Route::get('daily-closures/{date}/status', [DailyClosureController::class, 'status']);
    Route::post('daily-closures', [DailyClosureController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:doctor'])->prefix('doctor')->group(function (): void {
    Route::get('profile', [DoctorAreaController::class, 'profile']);
    Route::patch('profile', [DoctorAreaController::class, 'updateProfile']);
    Route::patch('password', [DoctorAreaController::class, 'changePassword']);
    Route::get('commissions', [DoctorAreaController::class, 'commissions']);
    Route::get('patients', [DoctorAreaController::class, 'patients']);
    Route::get('patients/{patient}/history', [DoctorAreaController::class, 'patientHistory']);
});
