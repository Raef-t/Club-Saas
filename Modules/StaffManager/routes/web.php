<?php

use Illuminate\Support\Facades\Route;
use Modules\StaffManager\Http\Controllers\StaffManagerController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('staffmanagers', StaffManagerController::class)->names('staffmanager');
});
