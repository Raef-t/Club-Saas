<?php

use Illuminate\Support\Facades\Route;

use Modules\AttendanceManager\Http\Controllers\Api\V1\UnifiedAttendanceController;
use Modules\AttendanceManager\Http\Controllers\Api\V1\ReceptionAttendanceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {



    // Unified Attendance API (Check-in, Check-out, History for all user types)
    Route::post('attendances/check-in', [UnifiedAttendanceController::class, 'checkIn']);
    Route::post('attendances/check-out/{attendanceId}', [UnifiedAttendanceController::class, 'checkOut']);
    Route::get('attendances/history', [UnifiedAttendanceController::class, 'history']);


    // ── Reception Desk Workflow ──────────────────────────────────────────────
    // 1. Browse player's active subscriptions (to pick one for session deduction)
    Route::get('reception/members/{memberId}/subscriptions', [ReceptionAttendanceController::class, 'memberSubscriptions']);

    // 2. List all lockers in a branch (pass ?branch_id=1&available_only=1)
    Route::get('reception/lockers', [ReceptionAttendanceController::class, 'availableLockers']);

    // 3. Assign a locker key to an open attendance (post check-in or during check-in)
    Route::post('attendances/{attendanceId}/assign-locker', [ReceptionAttendanceController::class, 'assignLocker']);

    // 4. Release the locker key: return it or transfer it to a guest
    Route::post('attendances/{attendanceId}/release-locker', [ReceptionAttendanceController::class, 'releaseLocker']);

    // 5. Update locker holder at any time (change who currently holds the key)
    Route::patch('lockers/{lockerId}/holder', [ReceptionAttendanceController::class, 'updateLockerHolder']);

    // 6. Free a locker directly – regardless of attendance (e.g. staff/coach permanent locker)
    Route::delete('lockers/{lockerId}/holder', [ReceptionAttendanceController::class, 'freeLocker']);
    // ────────────────────────────────────────────────────────────────────────────



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
