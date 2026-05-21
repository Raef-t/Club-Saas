<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\SubscriptionPlanController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\PlayerSubscriptionController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\AttendanceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Attendance
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('attendance/check-out/{id}', [AttendanceController::class, 'checkOut']);

    // Subscription Plans CRUD
    Route::apiResource('subscription-plans', SubscriptionPlanController::class);

    // Player Subscriptions — Actions
    Route::post('player-subscriptions/{id}/freeze', [PlayerSubscriptionController::class, 'freeze']);
    Route::post('player-subscriptions/{id}/unfreeze', [PlayerSubscriptionController::class, 'unfreeze']);
    Route::post('player-subscriptions/{id}/renew', [PlayerSubscriptionController::class, 'renew']);
    Route::post('player-subscriptions/{id}/cancel', [PlayerSubscriptionController::class, 'cancel']);
    Route::post('player-subscriptions/{id}/payment', [PlayerSubscriptionController::class, 'recordPayment']);

    // Player Subscriptions CRUD
    Route::apiResource('player-subscriptions', PlayerSubscriptionController::class)->except(['update', 'destroy']);
});
