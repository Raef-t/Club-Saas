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

    // Authenticated Member's Activities (with period filtering)
    Route::get('my-activities', [MemberAttendanceController::class, 'myActivities']);
    
    // QR Attendance Endpoints (Mobile App / General)
    Route::get('qr', [\Modules\AttendanceManager\Http\Controllers\Api\V1\QRController::class, 'show']);
    Route::post('qr/generate', [\Modules\AttendanceManager\Http\Controllers\Api\V1\QRController::class, 'generate']);
    Route::post('qr/check-in', [\Modules\AttendanceManager\Http\Controllers\Api\V1\QRController::class, 'checkIn']);
    Route::post('qr/check-out', [\Modules\AttendanceManager\Http\Controllers\Api\V1\QRController::class, 'checkOut']);

});

// Hardware Gate Devices API (Authenticates via custom device token internally)
Route::prefix('v1/gates')->group(function () {
    Route::post('scan', [\Modules\AttendanceManager\Http\Controllers\Api\V1\GateController::class, 'scan']);
});
