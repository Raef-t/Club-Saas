<?php

use Illuminate\Support\Facades\Route;
use Modules\MemberManager\Http\Controllers\Api\V1\MemberController;
use Modules\MemberManager\Http\Controllers\Api\V1\MemberHealthProfileController;
use Modules\MemberManager\Http\Controllers\Api\V1\MemberMeasurementController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('members', MemberController::class);
    Route::apiResource('health-profiles', MemberHealthProfileController::class);
    Route::apiResource('measurements', MemberMeasurementController::class);
});
