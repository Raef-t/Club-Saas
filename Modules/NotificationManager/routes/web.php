<?php

use Illuminate\Support\Facades\Route;
use Modules\NotificationManager\Http\Controllers\NotificationManagerController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('notificationmanagers', NotificationManagerController::class)->names('notificationmanager');
});
