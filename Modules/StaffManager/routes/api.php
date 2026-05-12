<?php

use Illuminate\Support\Facades\Route;
use Modules\StaffManager\Http\Controllers\StaffManagerController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('staffmanagers', StaffManagerController::class)->names('staffmanager');
});
