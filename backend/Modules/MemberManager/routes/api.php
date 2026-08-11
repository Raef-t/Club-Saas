<?php

use Illuminate\Support\Facades\Route;

use Modules\MemberManager\Http\Controllers\Api\V1\PlayerRegistrationController;
use Modules\MemberManager\Http\Controllers\Api\V1\MemberHealthProfileController;
use Modules\MemberManager\Http\Controllers\Api\V1\MemberMeasurementController;

Route::middleware(['auth:sanctum', 'check.permission'])->prefix('v1')->group(function () {
    // Player Registration & Updates
    Route::get('members/stats', [PlayerRegistrationController::class, 'stats']);
    Route::get('members/trashed', [PlayerRegistrationController::class, 'trashed']);
    Route::get('members', [PlayerRegistrationController::class, 'index']);
    Route::post('members/register', [PlayerRegistrationController::class, 'register']);
    Route::get('members/{id}', [PlayerRegistrationController::class, 'show']);
    Route::put('members/{id}', [PlayerRegistrationController::class, 'update']);
    Route::post('members/{id}/photo', [PlayerRegistrationController::class, 'updatePhoto']);
    Route::post('members/{id}/restore', [PlayerRegistrationController::class, 'restore']);
    Route::delete('members/{id}', [PlayerRegistrationController::class, 'destroy']);
    Route::post('members/{id}/restore', [PlayerRegistrationController::class, 'restore']);


    // Member Health Profiles CRUD
    Route::prefix('member/health-profiles')->group(function () {
        Route::get('/', [MemberHealthProfileController::class, 'index']);
        Route::post('/', [MemberHealthProfileController::class, 'store']);
        Route::get('{id}', [MemberHealthProfileController::class, 'show']);
        Route::put('{id}', [MemberHealthProfileController::class, 'update']);
        Route::delete('{id}', [MemberHealthProfileController::class, 'destroy']);
    });

    // Member Measurements CRUD
    Route::prefix('member/measurements')->group(function () {
        Route::get('/', [MemberMeasurementController::class, 'index']);
        Route::post('/', [MemberMeasurementController::class, 'store']);
        Route::get('report', [MemberMeasurementController::class, 'report']);
        Route::get('{id}', [MemberMeasurementController::class, 'show']);
        Route::put('{id}', [MemberMeasurementController::class, 'update']);
        Route::delete('{id}', [MemberMeasurementController::class, 'destroy']);
    });


    // Member Dashboard
    Route::get('member/dashboard', [\Modules\MemberManager\Http\Controllers\Api\V1\MemberDashboardController::class, 'index']);

    // Me — Authenticated Member Endpoints
    Route::prefix('me')->group(function () {
        Route::post('evaluations', [\Modules\MemberManager\Http\Controllers\Api\V1\Me\EvaluationController::class, 'store']);
    });
});
