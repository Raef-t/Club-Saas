<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\ExtraServiceController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\SubscriptionPlanController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\PlayerSubscriptionController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\SubscriptionFreezeController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\SubscriptionPlanActivityController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\PlayerSubscriptionItemController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\PlayerSubscriptionServiceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {


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

    // Missing basic CRUD endpoints
    Route::apiResource('subscription-freezes', SubscriptionFreezeController::class);
    Route::apiResource('subscription-plan-activities', SubscriptionPlanActivityController::class);
    Route::apiResource('player-subscription-items', PlayerSubscriptionItemController::class);

    // Extra Services
    Route::apiResource('extra-services', ExtraServiceController::class);
    Route::apiResource('player-subscription-services', PlayerSubscriptionServiceController::class);
});
