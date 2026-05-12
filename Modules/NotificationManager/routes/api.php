<?php

use Illuminate\Support\Facades\Route;
use Modules\NotificationManager\Http\Controllers\Api\V1\NotificationTemplateController;
use Modules\NotificationManager\Http\Controllers\Api\V1\NotificationLogController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Stats & History
    Route::get('notification-stats', [NotificationLogController::class, 'stats']);
    Route::get('notification-logs', [NotificationLogController::class, 'index']);

    // Templates
    Route::post('notification-templates/{id}/toggle', [NotificationTemplateController::class, 'toggleStatus']);
    Route::post('notification-templates/{slug}/test', [NotificationTemplateController::class, 'testSend']);
    Route::apiResource('notification-templates', NotificationTemplateController::class);
});
