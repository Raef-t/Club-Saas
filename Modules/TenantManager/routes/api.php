<?php

use Illuminate\Support\Facades\Route;
use Modules\TenantManager\Http\Controllers\TenantManagerController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('tenantmanagers', TenantManagerController::class)->names('tenantmanager');
});
