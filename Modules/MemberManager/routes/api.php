<?php

use Illuminate\Support\Facades\Route;
use Modules\MemberManager\Http\Controllers\Api\V1\MemberController;
use Modules\MemberManager\Http\Controllers\Api\V1\MemberHealthProfileController;
use Modules\MemberManager\Http\Controllers\Api\V1\MemberMeasurementController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('members', MemberController::class);
    Route::apiResource('health-profiles', MemberHealthProfileController::class);
    Route::apiResource('measurements', MemberMeasurementController::class);

    // Player Unavailabilities
    Route::get('members/{member}/unavailabilities', [\Modules\MemberManager\Http\Controllers\Api\V1\PlayerUnavailabilityController::class, 'index']);
    Route::post('members/{member}/unavailabilities', [\Modules\MemberManager\Http\Controllers\Api\V1\PlayerUnavailabilityController::class, 'store']);
    Route::delete('members/{member}/unavailabilities/{unavailability}', [\Modules\MemberManager\Http\Controllers\Api\V1\PlayerUnavailabilityController::class, 'destroy']);

    // Member Dashboard
    Route::get('member/dashboard', [\Modules\MemberManager\Http\Controllers\Api\V1\MemberDashboardController::class, 'index']);

    // Me — Authenticated Member Endpoints
    Route::prefix('me')->group(function () {
        Route::get('preferences', [\Modules\MemberManager\Http\Controllers\Api\V1\Me\MePreferencesController::class, 'show']);
        Route::put('preferences', [\Modules\MemberManager\Http\Controllers\Api\V1\Me\MePreferencesController::class, 'update']);
        Route::post('physical-stats', [\Modules\MemberManager\Http\Controllers\Api\V1\Me\MePhysicalStatsController::class, 'update']);
        Route::post('evaluations', [\Modules\MemberManager\Http\Controllers\Api\V1\Me\EvaluationController::class, 'store']);
    });
});
