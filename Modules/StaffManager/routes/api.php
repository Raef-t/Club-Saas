<?php

use Illuminate\Support\Facades\Route;
use Modules\StaffManager\Http\Controllers\Api\V1\StaffController;
use Modules\StaffManager\Http\Controllers\Api\V1\PayrollController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Staff CRUD
    Route::apiResource('staff', StaffController::class);

    // Staff Actions
    Route::post('staff/{id}/schedule', [StaffController::class, 'setSchedule']);
    Route::post('staff/{id}/check-in', [StaffController::class, 'checkIn']);
    Route::post('staff/{id}/check-out', [StaffController::class, 'checkOut']);
    Route::patch('staff/{id}/toggle-status', [StaffController::class, 'toggleStatus']);
    Route::get('staff/{id}/attendance', [StaffController::class, 'getAttendance']);

    // Payroll
    Route::post('payroll-runs/{id}/generate-payslips', [PayrollController::class, 'generatePayslips']);
    Route::post('payroll-runs/{id}/approve', [PayrollController::class, 'approve']);
    Route::apiResource('payroll-runs', PayrollController::class)->except(['update', 'destroy']);
});
