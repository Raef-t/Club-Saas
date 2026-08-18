<?php

use Illuminate\Support\Facades\Route;
use Modules\ClubManager\Http\Controllers\Api\V1\BranchController;
use Modules\ClubManager\Http\Controllers\Api\V1\FacilityController;
use Modules\ClubManager\Http\Controllers\Api\V1\LockerController;
use Modules\ClubManager\Http\Controllers\Api\V1\ClubController;
use Modules\ClubManager\Http\Controllers\Api\V1\ClubSettingController;
use Modules\ClubManager\Http\Controllers\Api\V1\BranchHolidayController;
use Modules\ClubManager\Http\Controllers\Api\V1\BranchSettingController;
use Modules\ClubManager\Http\Controllers\Api\V1\DatabaseBackupController;

Route::middleware(['auth:sanctum', 'check.permission'])->prefix('v1')->group(function () {
    Route::get('clubs/trashed', [ClubController::class, 'trashed']);
    Route::post('clubs/{id}/restore', [ClubController::class, 'restore']);
    Route::post('clubs/{id}', [ClubController::class, 'update']);
    Route::apiResource('clubs', ClubController::class);

    Route::get('branches/stats', [BranchController::class, 'stats']);
    Route::get('branches/trashed', [BranchController::class, 'trashed']);
    Route::post('branches/{id}/restore', [BranchController::class, 'restore']);
    Route::apiResource('branches', BranchController::class);
    Route::patch('branches/{id}/toggle-status', [BranchController::class, 'toggleStatus']);
    
    Route::get('facilities/trashed', [FacilityController::class, 'trashed']);
    Route::post('facilities/{id}/restore', [FacilityController::class, 'restore']);
    Route::apiResource('facilities', FacilityController::class);
    Route::patch('facilities/{id}/toggle-status', [FacilityController::class, 'toggleStatus']);
    
    // Facility Working Hours
    Route::get('facilities/{facility}/working-hours', [\Modules\ClubManager\Http\Controllers\Api\V1\FacilityWorkingHourController::class, 'index']);
    Route::post('facilities/{facility}/working-hours', [\Modules\ClubManager\Http\Controllers\Api\V1\FacilityWorkingHourController::class, 'store']);
    Route::delete('facilities/{facility}/working-hours/{working_hour}', [\Modules\ClubManager\Http\Controllers\Api\V1\FacilityWorkingHourController::class, 'destroy']);
    
    // Branch Shifts
    Route::get('branches/{branch}/shifts', [\Modules\ClubManager\Http\Controllers\Api\V1\BranchShiftController::class, 'index']);
    Route::post('branches/{branch}/shifts', [\Modules\ClubManager\Http\Controllers\Api\V1\BranchShiftController::class, 'store']);
    Route::get('branches/{branch}/shifts/trashed', [\Modules\ClubManager\Http\Controllers\Api\V1\BranchShiftController::class, 'trashed']);
    Route::post('branches/{branch}/shifts/{id}/restore', [\Modules\ClubManager\Http\Controllers\Api\V1\BranchShiftController::class, 'restore']);
    Route::put('branches/{branch}/shifts/{shift}', [\Modules\ClubManager\Http\Controllers\Api\V1\BranchShiftController::class, 'update']);
    Route::delete('branches/{branch}/shifts/{shift}', [\Modules\ClubManager\Http\Controllers\Api\V1\BranchShiftController::class, 'destroy']);

    Route::get('lockers/holder/active', [LockerController::class, 'getByHolder']);
    Route::post('lockers/{id}/restore', [LockerController::class, 'restore']);
    Route::apiResource('lockers', LockerController::class);
    Route::post('lockers/{locker}/reservations', [LockerController::class, 'reserve']);
    Route::delete('lockers/{locker}/reservations/current', [LockerController::class, 'releaseCurrentReservation']);
    Route::patch('locker-reservations/{reservation}/holder', [LockerController::class, 'transferReservationHolder']);

    // Club Settings
    Route::get('clubs/{club}/settings', [ClubSettingController::class, 'show']);
    Route::put('clubs/{club}/settings', [ClubSettingController::class, 'update']);

    // Branch Holidays
    Route::get('branches/{branch}/holidays', [BranchHolidayController::class, 'index']);
    Route::post('branches/{branch}/holidays', [BranchHolidayController::class, 'store']);
    Route::get('holidays/trashed', [BranchHolidayController::class, 'trashed']);
    Route::post('holidays/{id}/restore', [BranchHolidayController::class, 'restore']);
    Route::apiResource('holidays', BranchHolidayController::class)->except(['index', 'store']);

    // Branch Settings
    Route::get('branches/{branch}/settings', [BranchSettingController::class, 'show']);
    Route::put('branches/{branch}/settings', [BranchSettingController::class, 'update']);

    // Database Backup
    Route::get('system/backup/download', [DatabaseBackupController::class, 'download']);
});
