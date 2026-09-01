<?php

use Illuminate\Support\Facades\Route;
use Modules\WalletManager\Http\Controllers\WalletManagerController;
use Modules\WalletManager\Http\Controllers\Api\V1\WalletController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware(['auth:sanctum', 'check.permission'])->prefix('v1')->group(function () {
    Route::apiResource('walletmanagers', WalletManagerController::class)->names('walletmanager');
    Route::get('people/{person_id}/wallet', [WalletController::class, 'show']);
    Route::post('people/{person_id}/wallet/deposit', [WalletController::class, 'deposit']);
});
