<?php

use Illuminate\Support\Facades\Route;
use Modules\ClubManager\Http\Controllers\ClubManagerController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('clubmanagers', ClubManagerController::class)->names('clubmanager');
});
