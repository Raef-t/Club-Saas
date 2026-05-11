<?php

use Illuminate\Support\Facades\Route;
use Modules\BranchManager\Http\Controllers\BranchManagerController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('branchmanagers', BranchManagerController::class)->names('branchmanager');
});
