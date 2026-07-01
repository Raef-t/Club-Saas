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

    // Staff Working Hours
    Route::get('staff/{staff}/working-hours', [\Modules\StaffManager\Http\Controllers\Api\V1\StaffWorkingHourController::class, 'index']);
    Route::post('staff/{staff}/working-hours', [\Modules\StaffManager\Http\Controllers\Api\V1\StaffWorkingHourController::class, 'store']);
    Route::delete('staff/{staff}/working-hours/{working_hour}', [\Modules\StaffManager\Http\Controllers\Api\V1\StaffWorkingHourController::class, 'destroy']);


    // Payroll & Payslips
    Route::post('payroll-runs/{id}/generate-payslips', [PayrollController::class, 'generatePayslips']);
    Route::post('payroll-runs/{id}/approve', [PayrollController::class, 'approve']);
    Route::post('payroll-runs/{id}/process', [PayrollController::class, 'process']);
    Route::apiResource('payroll-runs', PayrollController::class)->except(['update', 'destroy']);
    Route::apiResource('payslips', PayslipController::class);

    // Coach Management
    Route::prefix('coaches')->group(function () {
        Route::get('/stats', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'stats']);
        Route::get('/', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'index']);
        Route::post('/', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'store']);
        Route::get('/{id}', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'show']);
        Route::patch('/{id}', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'update']);
        Route::post('/{id}/activities', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'assignActivities']);
        Route::delete('/{id}/activities/{activityId}', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'removeActivity']);
        Route::post('/{id}/certifications', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'uploadCertification']);
        Route::get('/{id}/certifications', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'getCertifications']);
        Route::delete('/{id}', [\Modules\StaffManager\Http\Controllers\Api\V1\CoachController::class, 'destroy']);
    });

    // Shifts
    Route::apiResource('staff-shifts', StaffShiftController::class);
});
