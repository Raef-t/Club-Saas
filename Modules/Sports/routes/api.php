<?php

use Illuminate\Support\Facades\Route;
use Modules\Sports\Http\Controllers\SportsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('sports', SportsController::class)->names('sports');
});
