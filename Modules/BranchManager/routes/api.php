<?php

use Illuminate\Support\Facades\Route;
use Modules\BranchManager\Http\Controllers\Api\V1\BranchController;
use Modules\BranchManager\Http\Controllers\Api\V1\FacilityController;
use Modules\BranchManager\Http\Controllers\Api\V1\LockerController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('branches', BranchController::class);
    Route::patch('branches/{id}/toggle-status', [BranchController::class, 'toggleStatus']);
    
    Route::apiResource('facilities', FacilityController::class);
    Route::patch('facilities/{id}/toggle-status', [FacilityController::class, 'toggleStatus']);
    
    Route::apiResource('lockers', LockerController::class);
    Route::patch('lockers/{id}/toggle-status', [LockerController::class, 'toggleStatus']);
});
