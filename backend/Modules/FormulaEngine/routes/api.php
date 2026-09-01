<?php

use Illuminate\Support\Facades\Route;
use Modules\FormulaEngine\Http\Controllers\Api\V1\FormulaController;
use Modules\FormulaEngine\Http\Controllers\Api\V1\FormulaExecutionController;

Route::middleware(['auth:sanctum', 'check.permission'])->prefix('v1')->group(function () {

    // ─── Admin: Formula Definitions ───────────────────────────────────────
    Route::prefix('admin/formulas')->group(function () {
        Route::get('/',            [FormulaController::class, 'index']);
        Route::post('/',           [FormulaController::class, 'store']);
        Route::get('/{formula}',   [FormulaController::class, 'show']);
        Route::put('/{formula}',   [FormulaController::class, 'update']);
        Route::delete('/{formula}',[FormulaController::class, 'destroy']);
        Route::post('/validate',   [FormulaController::class, 'validateFormula']);
    });

    // ─── Runtime Evaluation ───────────────────────────────────────────────
    Route::prefix('formulas')->group(function () {
        Route::post('/evaluate-batch',      [FormulaExecutionController::class, 'evaluateBatch']);
        Route::post('/{key}/evaluate',      [FormulaExecutionController::class, 'evaluate']);
    });
});
