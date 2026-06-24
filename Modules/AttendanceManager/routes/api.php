<?php

use Illuminate\Support\Facades\Route;
use Modules\AttendanceManager\Http\Controllers\Api\V1\StaffAttendanceController;
use Modules\AttendanceManager\Http\Controllers\Api\V1\MemberAttendanceController;
use Modules\AttendanceManager\Http\Controllers\Api\V1\UnifiedAttendanceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // Staff Attendance (CRUD only)
    Route::apiResource('staff-attendances', StaffAttendanceController::class);

    // Member Attendance (CRUD only)
    Route::apiResource('member-attendances', MemberAttendanceController::class);

    // Unified Attendance API (Check-in, Check-out, History for all user types)
    Route::post('attendances/check-in', [UnifiedAttendanceController::class, 'checkIn']);
    Route::post('attendances/check-out/{attendanceId}', [UnifiedAttendanceController::class, 'checkOut']);
    Route::get('attendances/history', [UnifiedAttendanceController::class, 'history']);

    // Authenticated Member's Activities (with period filtering)
    Route::get('my-activities', [MemberAttendanceController::class, 'myActivities']);

    // QR Attendance Endpoints (Mobile App / General)
    Route::get('qr/screen', [\Modules\AttendanceManager\Http\Controllers\Api\V1\QRController::class, 'show']);
    Route::post('qr/generate', [\Modules\AttendanceManager\Http\Controllers\Api\V1\QRController::class, 'generate']);
    Route::post('qr/check-in', [\Modules\AttendanceManager\Http\Controllers\Api\V1\QRController::class, 'checkIn']);
    Route::post('qr/check-out', [\Modules\AttendanceManager\Http\Controllers\Api\V1\QRController::class, 'checkOut']);
});

// Hardware Gate Devices API (Authenticates via custom device token internally)
Route::prefix('v1/gates')->group(function () {
    Route::post('scan', [\Modules\AttendanceManager\Http\Controllers\Api\V1\GateController::class, 'scan']);
});
