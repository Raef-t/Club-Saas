<?php

use Illuminate\Support\Facades\Route;
use Modules\TenantManager\Http\Controllers\TenantManagerController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tenantmanagers', TenantManagerController::class)->names('tenantmanager');
});
