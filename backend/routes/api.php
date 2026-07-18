<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ProcedureController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('doctors', [DoctorController::class, 'registerDoctor']);
    Route::post('procedures', [ProcedureController::class, 'store']);
    Route::match(['put', 'patch'], 'procedures/{procedure}', [ProcedureController::class, 'update']);
    Route::delete('procedures/{procedure}', [ProcedureController::class, 'destroy']);

});

Route::middleware(['auth:sanctum', 'role:admin,receptionist,doctor'])->group(function (): void {
    Route::get('doctors', [DoctorController::class, 'index']);
    Route::get('doctors/{doctor}', [DoctorController::class, 'show']);
    Route::get('procedures', [ProcedureController::class, 'index']);
    Route::get('procedures/{procedure}', [ProcedureController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:admin,receptionist'])->group(function (): void {
    Route::post('attendances', [AttendanceController::class, 'store']);
});
