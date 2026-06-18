<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
});
