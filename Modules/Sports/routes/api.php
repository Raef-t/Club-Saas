<?php

use Illuminate\Support\Facades\Route;
use Modules\Sports\Http\Controllers\Api\V1\ActivityController;
use Modules\Sports\Http\Controllers\Api\V1\SessionController;

use Modules\Sports\Http\Controllers\Api\V1\StaffCommissionRuleController;
use Modules\Sports\Http\Controllers\Api\V1\StaffActivityController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Activities CRUD
    Route::apiResource('activities', ActivityController::class);

    // Sessions
    Route::get('sessions/weekly-schedule', [SessionController::class, 'weeklySchedule']);
    Route::apiResource('sessions', SessionController::class);

    // Staff Commission Rules & Activities
    Route::apiResource('staff-commission-rules', StaffCommissionRuleController::class);
    Route::apiResource('staff-activities', StaffActivityController::class);
});
