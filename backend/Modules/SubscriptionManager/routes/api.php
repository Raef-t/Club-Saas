<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\SubscriptionPlanController;


use Modules\SubscriptionManager\Http\Controllers\Api\V1\PlayerSubscriptionController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\SubscriptionPlanActivityController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\PlayerSubscriptionItemController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\InvoiceController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\OfferController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\SubscriptionReportController;
use Modules\SubscriptionManager\Http\Controllers\Api\V1\PaymentController;

Route::middleware(['auth:sanctum', 'check.permission'])->prefix('v1')->group(function () {

    // Subscription & Renewal Reports
    Route::get('reports/subscriptions', [SubscriptionReportController::class, 'allSubscriptionsReport']);
    Route::get('reports/subscriptions/renewal-status', [SubscriptionReportController::class, 'renewalStatusReport']);
    Route::get('reports/subscriptions/frozen-terminated', [SubscriptionReportController::class, 'frozenAndTerminatedReport']);
    Route::get('reports/sessions/time-capacity', [SubscriptionReportController::class, 'timeCapacityReport']);
    Route::get('reports/attendance/peak-hours', [SubscriptionReportController::class, 'peakHoursReport']);
    Route::get('reports/shifts/attendance', [SubscriptionReportController::class, 'shiftAttendanceReport']);
    Route::get('reports/coaches/subscriptions', [SubscriptionReportController::class, 'coachSubscriptionReport']);

    // Authenticated Member's Invoices
    Route::get('my-invoices', [InvoiceController::class, 'myInvoices']);


    // Subscription Plans CRUD
    Route::get('subscription-plans/trashed', [SubscriptionPlanController::class, 'trashed']);
    Route::post('subscription-plans/{id}/restore', [SubscriptionPlanController::class, 'restore']);
    Route::get('subscription-plans/registration', [SubscriptionPlanController::class, 'registrationPlans']);
    Route::get('subscription-plans/{id}/players', [SubscriptionPlanController::class, 'players']);
    Route::get('subscription-plans/{id}/delete-check', [SubscriptionPlanController::class, 'deleteCheck']);
    Route::post('subscription-plans/{id}/restore', [SubscriptionPlanController::class, 'restore']);
    Route::apiResource('subscription-plans', SubscriptionPlanController::class);

    // Offers CRUD & Actions
    Route::get('offers/trashed', [OfferController::class, 'trashed']);
    Route::post('offers/{id}/restore', [OfferController::class, 'restore']);
    Route::post('offers/{id}/subscribe', [OfferController::class, 'subscribe']);
    Route::apiResource('offers', OfferController::class);

    // Player Subscriptions — Actions
    Route::post('player-subscriptions/{id}/freeze', [PlayerSubscriptionController::class, 'freeze']);
    Route::post('player-subscriptions/{id}/unfreeze', [PlayerSubscriptionController::class, 'unfreeze']);
    Route::post('player-subscriptions/{id}/renew', [PlayerSubscriptionController::class, 'renew']);
    Route::post('player-subscriptions/{id}/cancel', [PlayerSubscriptionController::class, 'cancel']);
    Route::post('player-subscriptions/{id}/payment', [PlayerSubscriptionController::class, 'recordPayment']);
    Route::post('player-subscriptions/{id}/restore', [PlayerSubscriptionController::class, 'restore']);

    // Player Subscriptions CRUD
    Route::apiResource('player-subscriptions', PlayerSubscriptionController::class);

    // Payments CRUD & Actions
    Route::get('payments/trashed', [PaymentController::class, 'trashed']);
    Route::post('payments/{id}/restore', [PaymentController::class, 'restore']);
    Route::apiResource('payments', PaymentController::class);

    Route::get('subscription-plan-activities/trashed', [SubscriptionPlanActivityController::class, 'trashed']);
    Route::post('subscription-plan-activities/{id}/restore', [SubscriptionPlanActivityController::class, 'restore']);
    Route::apiResource('subscription-plan-activities', SubscriptionPlanActivityController::class);
    Route::apiResource('player-subscription-items', PlayerSubscriptionItemController::class);


});
