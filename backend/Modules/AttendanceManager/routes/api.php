<?php

use Illuminate\Support\Facades\Route;

use Modules\AttendanceManager\Http\Controllers\Api\V1\UnifiedAttendanceController;
use Modules\AttendanceManager\Http\Controllers\Api\V1\ReceptionAttendanceController;

use Modules\AttendanceManager\Http\Controllers\Api\V1\AttendanceDashboardController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // Reception & Attendance Dashboard Real-Time Stats
    Route::get('attendance-manager/dashboard-stats', [AttendanceDashboardController::class, 'stats']);
    Route::get('attendance-manager/dashboard-stats-stream', [AttendanceDashboardController::class, 'statsStream']);



    // Unified Attendance API (Check-in, Check-out, History for all user types)
    Route::post('attendances/check-in', [UnifiedAttendanceController::class, 'checkIn']);
    Route::post('attendances/check-out/{attendanceId}', [UnifiedAttendanceController::class, 'checkOut']);
    Route::post('attendances/bulk-check-out', [UnifiedAttendanceController::class, 'bulkCheckOut']);
    Route::get('attendances/history', [UnifiedAttendanceController::class, 'history']);
    Route::delete('attendances/{id}', [UnifiedAttendanceController::class, 'destroy']);
    Route::post('attendances/{id}/restore', [UnifiedAttendanceController::class, 'restore']);


    // ── Reception Desk Workflow ──────────────────────────────────────────────
    // 1. Browse player's active subscriptions (to pick one for session deduction)
    Route::get('reception/members/{memberId}/subscriptions', [ReceptionAttendanceController::class, 'memberSubscriptions']);
    
    // 1.5. Deduct session from a specific subscription after check-in
    Route::post('reception/attendances/{attendanceId}/deduct', [ReceptionAttendanceController::class, 'deductSession']);

    // 1.6. Rollback attendance and return deducted session
    Route::delete('reception/attendances/{attendanceId}/rollback', [ReceptionAttendanceController::class, 'rollbackAttendance']);


    // ────────────────────────────────────────────────────────────────────────────



    // QR Attendance Endpoints (Mobile App / General)

    Route::post('qr/check-in', [\Modules\AttendanceManager\Http\Controllers\Api\V1\QRController::class, 'checkIn']);
    Route::post('qr/check-out', [\Modules\AttendanceManager\Http\Controllers\Api\V1\QRController::class, 'checkOut']);
});

// Hardware Gate Devices API (Authenticates via custom device token internally)
Route::prefix('v1/gates')->group(function () {
    Route::post('scan', [\Modules\AttendanceManager\Http\Controllers\Api\V1\GateController::class, 'scan']);
});
