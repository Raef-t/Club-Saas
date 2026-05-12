<?php

use Illuminate\Support\Facades\Route;
use Modules\MemberManager\Http\Controllers\Api\V1\MemberController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('members/{id}/health-profile', [MemberController::class, 'getHealthProfile']);
    Route::get('members/{id}/measurements', [MemberController::class, 'getMeasurements']);
    Route::post('members/{id}/measurements', [MemberController::class, 'recordMeasurement']);
    Route::apiResource('members', MemberController::class);
});
