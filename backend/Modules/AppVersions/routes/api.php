<?php

use Illuminate\Support\Facades\Route;
use Modules\AppVersions\Http\Controllers\Api\V1\AppVersionsController;

// ─── Public Client Endpoints ────────────────────────────────────────────────
Route::prefix('v1/app')->group(function () {
    Route::get('check-version', [AppVersionsController::class, 'checkVersion']);
});

// ─── Admin Management Endpoints ─────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'check.permission'])->prefix('v1')->group(function () {
    Route::patch('app-versions/{id}/toggle-status', [AppVersionsController::class, 'toggleStatus']);
    Route::apiResource('app-versions', AppVersionsController::class);
});
