<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\Api\UploadController;
use Modules\Core\Http\Controllers\Api\V1\AuditLogController;

Route::middleware(['auth:sanctum', 'check.permission'])->prefix('v1')->group(function () {
    Route::apiResource('cores', CoreController::class)->names('core');
    Route::post('upload', [UploadController::class, 'upload']);

    // Audit Logs Routes
    Route::get('audits/meta', [AuditLogController::class, 'meta']);
    Route::get('audits', [AuditLogController::class, 'index']);
    Route::get('audits/{id}', [AuditLogController::class, 'show']);
});

