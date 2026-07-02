<?php

use Illuminate\Support\Facades\Route;
use Modules\Sports\Http\Controllers\Api\V1\ActivityController;
use Modules\Sports\Http\Controllers\Api\V1\SessionController;
use Modules\Sports\Http\Controllers\Api\V1\SessionBookingController;

use Modules\Sports\Http\Controllers\Api\V1\StaffCommissionRuleController;
use Modules\Sports\Http\Controllers\Api\V1\StaffActivityController;

use Modules\Sports\Http\Controllers\Api\V1\ActivityTypeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Activity Types CRUD
    Route::apiResource('activity-types', ActivityTypeController::class);

    // Activities CRUD
    Route::get('activities/stats', [ActivityController::class, 'stats']);
    Route::apiResource('activities', ActivityController::class);

    // Session Templates
    Route::post('session-templates/generate', [Modules\Sports\Http\Controllers\Api\V1\SessionTemplateController::class, 'generate']);
    Route::apiResource('session-templates', Modules\Sports\Http\Controllers\Api\V1\SessionTemplateController::class);

    // Sessions
    Route::get('sessions/weekly-schedule', [SessionController::class, 'weeklySchedule']);
    Route::get('sessions/my-schedule', [SessionController::class, 'playerSchedule']);
    Route::patch('sessions/{session}/complete', [SessionController::class, 'markCompleted']);
    Route::apiResource('sessions', SessionController::class);

    // Session Bookings
    Route::post('sessions/{id}/book', [SessionBookingController::class, 'book']);
    Route::post('bookings/{id}/cancel', [SessionBookingController::class, 'cancel']);
    Route::get('bookings', [SessionBookingController::class, 'index']);

    // Staff Commission Rules & Activities
    Route::apiResource('staff-commission-rules', StaffCommissionRuleController::class);
    Route::apiResource('staff-activities', StaffActivityController::class);
});
