<?php

use Illuminate\Support\Facades\Route;
use Modules\MemberManager\Http\Controllers\MemberManagerController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('membermanagers', MemberManagerController::class)->names('membermanager');
});
