<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\Api\AuthController;
use Modules\Authentication\Http\Controllers\Api\PeopleController;

Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('people', [PeopleController::class, 'store']);
});
