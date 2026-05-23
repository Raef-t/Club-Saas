<?php

use Illuminate\Support\Facades\Route;
use Modules\ClubManager\Http\Controllers\Api\V1\BranchController;
use Modules\ClubManager\Http\Controllers\Api\V1\FacilityController;
use Modules\ClubManager\Http\Controllers\Api\V1\LockerController;
use Modules\ClubManager\Http\Controllers\Api\V1\ClubController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('clubs', ClubController::class);

    Route::apiResource('branches', BranchController::class);
    Route::patch('branches/{id}/toggle-status', [BranchController::class, 'toggleStatus']);
    
    Route::apiResource('facilities', FacilityController::class);
    Route::patch('facilities/{id}/toggle-status', [FacilityController::class, 'toggleStatus']);
    
    // Facility Working Hours
    Route::get('facilities/{facility}/working-hours', [\Modules\ClubManager\Http\Controllers\Api\V1\FacilityWorkingHourController::class, 'index']);
    Route::post('facilities/{facility}/working-hours', [\Modules\ClubManager\Http\Controllers\Api\V1\FacilityWorkingHourController::class, 'store']);
    Route::delete('facilities/{facility}/working-hours/{working_hour}', [\Modules\ClubManager\Http\Controllers\Api\V1\FacilityWorkingHourController::class, 'destroy']);
    
    Route::apiResource('lockers', LockerController::class);
    Route::patch('lockers/{id}/toggle-status', [LockerController::class, 'toggleStatus']);

    // Club Settings
    Route::get('clubs/{club}/settings', [\Modules\ClubManager\Http\Controllers\Api\V1\ClubSettingController::class, 'show']);
    Route::put('clubs/{club}/settings', [\Modules\ClubManager\Http\Controllers\Api\V1\ClubSettingController::class, 'update']);
});
