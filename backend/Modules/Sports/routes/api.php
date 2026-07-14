<?php

use Illuminate\Support\Facades\Route;
use Modules\Sports\Http\Controllers\Api\V1\ActivityController;

use Modules\Sports\Http\Controllers\Api\V1\StaffCommissionRuleController;
use Modules\Sports\Http\Controllers\Api\V1\StaffActivityController;

use Modules\Sports\Http\Controllers\Api\V1\ActivityTypeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Activity Types CRUD
    Route::patch('activity-types/{activity_type}/settings', [ActivityTypeController::class, 'updateSettings']);
    Route::apiResource('activity-types', ActivityTypeController::class);

    // Activities CRUD
    Route::get('activities/stats', [ActivityController::class, 'stats']);
    Route::apiResource('activities', ActivityController::class);

    // Session Templates
    Route::get('session-templates/schedule', [Modules\Sports\Http\Controllers\Api\V1\SessionTemplateController::class, 'schedule']);
    Route::post('session-templates/{id}/cancel', [Modules\Sports\Http\Controllers\Api\V1\SessionTemplateController::class, 'cancelSession']);
    Route::apiResource('session-templates', Modules\Sports\Http\Controllers\Api\V1\SessionTemplateController::class);

    // Staff Commission Rules & Activities
    Route::apiResource('staff-commission-rules', StaffCommissionRuleController::class);
    Route::apiResource('staff-activities', StaffActivityController::class);
});
