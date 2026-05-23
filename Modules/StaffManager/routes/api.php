<?php

use Illuminate\Support\Facades\Route;
use Modules\StaffManager\Http\Controllers\Api\V1\StaffController;
use Modules\StaffManager\Http\Controllers\Api\V1\PayrollController;
use Modules\StaffManager\Http\Controllers\Api\V1\PayslipController;
use Modules\StaffManager\Http\Controllers\Api\V1\StaffShiftController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Staff CRUD
    Route::apiResource('staff', StaffController::class);

    // Staff Actions
    Route::post('staff/{id}/schedule', [StaffController::class, 'setSchedule']);
    Route::patch('staff/{id}/toggle-status', [StaffController::class, 'toggleStatus']);
    Route::post('staff/{id}/sync-branches', [StaffController::class, 'syncBranches']);

    // Staff Unavailabilities
    Route::get('staff/{staff}/unavailabilities', [\Modules\StaffManager\Http\Controllers\Api\V1\StaffUnavailabilityController::class, 'index']);
    Route::post('staff/{staff}/unavailabilities', [\Modules\StaffManager\Http\Controllers\Api\V1\StaffUnavailabilityController::class, 'store']);
    Route::delete('staff/{staff}/unavailabilities/{unavailability}', [\Modules\StaffManager\Http\Controllers\Api\V1\StaffUnavailabilityController::class, 'destroy']);


    // Payroll & Payslips
    Route::post('payroll-runs/{id}/generate-payslips', [PayrollController::class, 'generatePayslips']);
    Route::post('payroll-runs/{id}/approve', [PayrollController::class, 'approve']);
    Route::post('payroll-runs/{id}/process', [PayrollController::class, 'process']);
    Route::apiResource('payroll-runs', PayrollController::class)->except(['update', 'destroy']);
    Route::apiResource('payslips', PayslipController::class);

    // Shifts
    Route::apiResource('staff-shifts', StaffShiftController::class);
});
