<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountController;
use Modules\Accounting\Http\Controllers\SafeController;
use Modules\Accounting\Http\Controllers\PartnerController;
use Modules\Accounting\Http\Controllers\CounterpartyController;
use Modules\Accounting\Http\Controllers\PeriodController;
use Modules\Accounting\Http\Controllers\JournalController;
use Modules\Accounting\Http\Controllers\ReportController;
use Modules\Accounting\Http\Controllers\ReconciliationController;

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'approved', 'force-password-change'],
    'prefix'     => 'accounting',
    'as'         => 'api.accounting.',
], function () {

    /*
    |--------------------------------------------------------------------------
    | الفترات المحاسبية — Accounting Periods
    |--------------------------------------------------------------------------
    */
    Route::get('periods', [PeriodController::class, 'index'])->name('periods.index');
    Route::post('periods', [PeriodController::class, 'store'])->name('periods.store');
    Route::get('periods/{id}', [PeriodController::class, 'show'])->name('periods.show');
    Route::post('periods/{id}/close', [PeriodController::class, 'close'])->name('periods.close');
    Route::post('periods/{id}/lock', [PeriodController::class, 'lock'])->name('periods.lock');
    Route::post('periods/{id}/reopen', [PeriodController::class, 'reopen'])->name('periods.reopen');

    /*
    |--------------------------------------------------------------------------
    | دليل الحسابات — Chart of Accounts
    |--------------------------------------------------------------------------
    */
    Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('accounts/{id}', [AccountController::class, 'show'])->name('accounts.show');
    Route::put('accounts/{id}', [AccountController::class, 'update'])->name('accounts.update');
    Route::get('accounts/{id}/ledger', [AccountController::class, 'ledger'])->name('accounts.ledger');

    /*
    |--------------------------------------------------------------------------
    | الصناديق والخزائن — Safes
    |--------------------------------------------------------------------------
    */
    Route::get('safes', [SafeController::class, 'index'])->name('safes.index');
    Route::post('safes', [SafeController::class, 'store'])->name('safes.store');
    Route::get('safes/{id}', [SafeController::class, 'show'])->name('safes.show');
    Route::put('safes/{id}', [SafeController::class, 'update'])->name('safes.update');
    Route::get('safes/{id}/statement', [SafeController::class, 'statement'])->name('safes.statement');

    /*
    |--------------------------------------------------------------------------
    | الشركاء — Partners
    |--------------------------------------------------------------------------
    */
    Route::get('partners', [PartnerController::class, 'index'])->name('partners.index');
    Route::post('partners', [PartnerController::class, 'store'])->name('partners.store');
    Route::get('partners/{id}', [PartnerController::class, 'show'])->name('partners.show');
    Route::put('partners/{id}', [PartnerController::class, 'update'])->name('partners.update');
    Route::get('partners/{id}/statement', [PartnerController::class, 'statement'])->name('partners.statement');

    /*
    |--------------------------------------------------------------------------
    | الأطراف — Counterparties
    |--------------------------------------------------------------------------
    */
    Route::get('counterparties', [CounterpartyController::class, 'index'])->name('counterparties.index');
    Route::post('counterparties', [CounterpartyController::class, 'store'])->name('counterparties.store');
    Route::get('counterparties/{id}', [CounterpartyController::class, 'show'])->name('counterparties.show');
    Route::put('counterparties/{id}', [CounterpartyController::class, 'update'])->name('counterparties.update');

    /*
    |--------------------------------------------------------------------------
    | سندات القيود المحاسبية — Journals
    |--------------------------------------------------------------------------
    */
    Route::get('journals', [JournalController::class, 'index'])->name('journals.index');
    Route::post('journals', [JournalController::class, 'store'])->name('journals.store');
    Route::get('journals/{id}', [JournalController::class, 'show'])->name('journals.show');
    Route::post('journals/{id}/post', [JournalController::class, 'post'])->name('journals.post');
    Route::post('journals/{id}/reverse', [JournalController::class, 'reverse'])->name('journals.reverse');

    /*
    |--------------------------------------------------------------------------
    | التقارير المالية — Financial Reports
    |--------------------------------------------------------------------------
    */
    Route::get('reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('reports/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
    Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');

    /*
    |--------------------------------------------------------------------------
    | تسوية الصناديق — Reconciliations
    |--------------------------------------------------------------------------
    */
    Route::get('reconciliations', [ReconciliationController::class, 'index'])->name('reconciliations.index');
    Route::post('reconciliations', [ReconciliationController::class, 'store'])->name('reconciliations.store');
    Route::get('reconciliations/{id}', [ReconciliationController::class, 'show'])->name('reconciliations.show');
});
