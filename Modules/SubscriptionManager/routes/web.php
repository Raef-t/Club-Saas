<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionManager\Http\Controllers\SubscriptionManagerController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('subscriptionmanagers', SubscriptionManagerController::class)->names('subscriptionmanager');
});
