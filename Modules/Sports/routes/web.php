<?php

use Illuminate\Support\Facades\Route;
use Modules\Sports\Http\Controllers\SportsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('sports', SportsController::class)->names('sports');
});
