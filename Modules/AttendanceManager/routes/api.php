<?php

use Illuminate\Support\Facades\Route;
use Modules\AttendanceManager\Http\Controllers\Api\V1\StaffAttendanceController;
use Modules\AttendanceManager\Http\Controllers\Api\V1\MemberAttendanceController;

Route::middleware(['auth:sanctum'])->prefix('v1/attendance')->group(function () {
    
    // Staff Attendance
    Route::apiResource('staff-attendances', StaffAttendanceController::class);
    Route::post('staff/{staffId}/check-in', [StaffAttendanceController::class, 'checkIn']);
    Route::post('staff/check-out/{attendanceId}', [StaffAttendanceController::class, 'checkOut']);
    Route::get('staff/{staffId}/history', [StaffAttendanceController::class, 'history']);

    // Member Attendance
    Route::apiResource('member-attendances', MemberAttendanceController::class);
    Route::post('members/check-in', [MemberAttendanceController::class, 'checkIn']);
    Route::post('members/check-out/{attendanceId}', [MemberAttendanceController::class, 'checkOut']);
    Route::get('members/{memberId}/history', [MemberAttendanceController::class, 'history']);

});
