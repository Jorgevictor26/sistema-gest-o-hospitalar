<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorController;


Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('doctors', [DoctorController::class, 'registerDoctor']);

});
