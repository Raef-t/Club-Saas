<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\SubscriptionPlanController;


use Modules\SubscriptionManager\Http\Controllers\Api\V1\PlayerSubscriptionController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\SubscriptionPlanActivityController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\PlayerSubscriptionItemController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\InvoiceController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\OfferController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\SubscriptionReportController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // Subscription & Renewal Reports
    Route::get('reports/subscriptions/renewal-status', [SubscriptionReportController::class, 'renewalStatusReport']);
    Route::get('reports/subscriptions/frozen-terminated', [SubscriptionReportController::class, 'frozenAndTerminatedReport']);
    Route::get('reports/sessions/time-capacity', [SubscriptionReportController::class, 'timeCapacityReport']);
    Route::get('reports/attendance/peak-hours', [SubscriptionReportController::class, 'peakHoursReport']);

    // Authenticated Member's Invoices
    Route::get('my-invoices', [InvoiceController::class, 'myInvoices']);


    // Subscription Plans CRUD
    Route::get('subscription-plans/registration', [SubscriptionPlanController::class, 'registrationPlans']);
    Route::apiResource('subscription-plans', SubscriptionPlanController::class);

    // Offers CRUD & Actions
    Route::post('offers/{id}/subscribe', [OfferController::class, 'subscribe']);
    Route::apiResource('offers', OfferController::class);

    // Player Subscriptions — Actions
    Route::post('player-subscriptions/{id}/freeze', [PlayerSubscriptionController::class, 'freeze']);
    Route::post('player-subscriptions/{id}/unfreeze', [PlayerSubscriptionController::class, 'unfreeze']);
    Route::post('player-subscriptions/{id}/renew', [PlayerSubscriptionController::class, 'renew']);
    Route::post('player-subscriptions/{id}/cancel', [PlayerSubscriptionController::class, 'cancel']);
    Route::post('player-subscriptions/{id}/payment', [PlayerSubscriptionController::class, 'recordPayment']);

    // Player Subscriptions CRUD
    Route::apiResource('player-subscriptions', PlayerSubscriptionController::class)->except(['update', 'destroy']);


    Route::apiResource('subscription-plan-activities', SubscriptionPlanActivityController::class);
    Route::apiResource('player-subscription-items', PlayerSubscriptionItemController::class);


});
