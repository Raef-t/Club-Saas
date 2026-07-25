<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\Api\AuthController;

use Modules\Authentication\Http\Controllers\Api\PersonContactController;

Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('change-photo', [AuthController::class, 'updatePhoto']);
        Route::delete('delete-photo', [AuthController::class, 'deletePhoto']);
    });
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('persons/{person}/contacts', [PersonContactController::class, 'getByPerson']);
    Route::apiResource('contacts', PersonContactController::class);
});
