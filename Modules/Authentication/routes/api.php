<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\Api\AuthController;
use Modules\Authentication\Http\Controllers\Api\PeopleController;
use Modules\Authentication\Http\Controllers\Api\UserController;

Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('people', [PeopleController::class, 'store']);
    
    // User Account Management
    Route::get('users', [UserController::class, 'index']);
    Route::post('users/create-account', [UserController::class, 'createAccount']);
    Route::patch('users/{id}/activate', [UserController::class, 'activate']);
    Route::patch('users/{id}/deactivate', [UserController::class, 'deactivate']);
});
