<?php

use Illuminate\Support\Facades\Route;
use Modules\WalletManager\Http\Controllers\WalletManagerController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('walletmanagers', WalletManagerController::class)->names('walletmanager');
});
