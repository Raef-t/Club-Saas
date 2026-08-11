<?php

use Illuminate\Support\Facades\Route;
use Modules\StaffManager\Http\Controllers\Api\V1\StaffController;
use Modules\StaffManager\Http\Controllers\Api\V1\PayrollController;
use Modules\StaffManager\Http\Controllers\Api\V1\PayslipController;
use Modules\StaffManager\Http\Controllers\Api\V1\StaffShiftController;

Route::middleware(['auth:sanctum', 'check.permission'])->prefix('v1')->group(function () {
    // Staff Trash & Restoration Actions
    Route::get('staff/trashed', [StaffController::class, 'trashed']);
    Route::post('staff/{id}/restore', [StaffController::class, 'restore']);

    // Staff CRUD
    Route::apiResource('staff', StaffController::class);

    // Staff Actions
    Route::post('staff/{id}/schedule', [StaffController::class, 'setSchedule']);
    Route::patch('staff/{id}/toggle-status', [StaffController::class, 'toggleStatus']);
    Route::post('staff/{id}/restore', [StaffController::class, 'restore']);
    Route::post('staff/{id}/photo', [StaffController::class, 'updatePhoto']);


    // Payroll & Payslips
    Route::post('payroll-runs/{id}/generate-payslips', [PayrollController::class, 'generatePayslips']);
    Route::post('payroll-runs/{id}/process', [PayrollController::class, 'process']);
    Route::apiResource('payroll-runs', PayrollController::class)->except(['update', 'destroy']);
    Route::get('payslips', [PayslipController::class, 'index']);
    Route::post('payslips/generate', [PayslipController::class, 'generate']);
    Route::post('payslips/confirm', [PayslipController::class, 'confirm']);
    Route::put('payslips/{payslip}', [PayslipController::class, 'update']);

    // Coach Management
    Route::prefix('coaches')->group(function () {
        Route::get('/stats', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'stats']);
        Route::get('/trashed', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'trashed']);
        Route::post('/{id}/restore', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'restore']);
        Route::get('/', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'index']);
        Route::post('/', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'store']);
        Route::get('/{id}', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'show']);
        Route::patch('/{id}', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'update']);
        Route::post('/{id}/photo', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'updatePhoto']);
        Route::post('/{id}/schedule', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'setSchedule']);
        Route::delete('/{id}', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'destroy']);
        Route::post('/{id}/restore', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'restore']);
    });

    // Shifts
    Route::apiResource('staff-shifts', StaffShiftController::class);
});
