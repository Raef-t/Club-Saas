<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\app\Http\Controllers\Api\AuthController;

Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
