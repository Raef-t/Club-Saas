<?php

use Illuminate\Support\Facades\Route;
use Modules\MemberManager\Http\Controllers\Api\V1\MemberController;

use Modules\MemberManager\Http\Controllers\Api\V1\PlayerRegistrationController;
use Modules\MemberManager\Http\Controllers\Api\V1\PlayerProfileController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Player Registration
    Route::post('players/register', [PlayerRegistrationController::class, 'register']);

    Route::apiResource('members', MemberController::class);


    // Player App Endpoints (Authenticated as Player)
    Route::prefix('player')->group(function () {
        Route::get('health-profiles', [PlayerProfileController::class, 'getAllHealthProfiles']);
        Route::delete('health-profiles/{id}', [PlayerProfileController::class, 'deleteHealthProfile']);
        Route::get('health-profile', [PlayerProfileController::class, 'getHealthProfile']);
        Route::put('health-profile', [PlayerProfileController::class, 'updateHealthProfile']);
        Route::get('measurements', [PlayerProfileController::class, 'getMeasurements']);
        Route::post('measurements', [PlayerProfileController::class, 'addMeasurement']);
        Route::get('all-measurements', [PlayerProfileController::class, 'getAllMeasurements']);
        Route::delete('measurements/{id}', [PlayerProfileController::class, 'deleteMeasurement']);
    });

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
        Route::post('evaluations', [\Modules\MemberManager\Http\Controllers\Api\V1\Me\EvaluationController::class, 'store']);
    });
});
