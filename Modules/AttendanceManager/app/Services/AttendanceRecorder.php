<?php

namespace Modules\AttendanceManager\Services;

use Modules\AttendanceManager\DTOs\CheckInAttempt;
use Modules\AttendanceManager\DTOs\CheckOutAttempt;
use Modules\AttendanceManager\DTOs\AttendanceDecision;
use Modules\AttendanceManager\Models\Attendance;
use Modules\AttendanceManager\Events\CheckInRecorded;
use Modules\AttendanceManager\Events\CheckInDenied;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceRecorder
{
    /**
     * Record the check-in attempt using the appropriate policy if the new engine is enabled.
     *
     * @param CheckInAttempt $attempt
     * @return AttendanceDecision
     */
    public function record(CheckInAttempt $attempt): AttendanceDecision
    {
        // 1. Feature Flag Layer
        if (!config('attendancemanager.use_new_engine', false)) {
            Log::info("Legacy attendance engine in use. Skipping new recorder evaluation.");
            return AttendanceDecision::deny("New attendance engine is not enabled in this environment.");
        }

        // 2. Lazy Container-Based Policy Resolution
        $bindingKey = "attendance.policy.{$attempt->attendableType}";

        if (!app()->bound($bindingKey)) {
            Log::error("No attendance policy registered for type: {$attempt->attendableType}");
            return AttendanceDecision::deny("Attendance type '{$attempt->attendableType}' is not supported.");
        }

        /** @var \Modules\AttendanceManager\Contracts\AttendancePolicy $policy */
        $policy = app($bindingKey);

        // 3. Policy-driven decision layer
        $decision = $policy->authorize($attempt);

        if (!$decision->isAllowed) {
            event(new CheckInDenied($attempt, $decision));
            return $decision;
        }

        // 4. Persistence & Event dispatching (Pure Recording Engine)
        return DB::transaction(function () use ($attempt, $decision) {
            $attendance = Attendance::create([
                'club_id' => $attempt->clubId,
                'attendable_type' => $attempt->attendableType,
                'attendable_id' => $attempt->attendableId,
                'branch_id' => $attempt->branchId,
                'check_in_at' => $attempt->timestamp,
                'status' => 'checked_in',
                'metadata' => array_merge($attempt->metadata, $decision->context),
            ]);

            event(new CheckInRecorded($attendance));

            return $decision;
        });
    }

    /**
     * Record a check-out attempt.
     */
    public function recordCheckOut(CheckOutAttempt $attempt): ?Attendance
    {
        return DB::transaction(function () use ($attempt) {
            $attendance = Attendance::where('club_id', $attempt->clubId)
                ->where('attendable_type', $attempt->attendableType)
                ->where('attendable_id', $attempt->attendableId)
                ->where('status', 'checked_in')
                ->latest('check_in_at')
                ->first();

            if (!$attendance) {
                return null;
            }

            $checkOutTime = $attempt->timestamp;
            $checkInTime = \Carbon\Carbon::parse($attendance->check_in_at);
            
            $durationMinutes = $checkInTime->diffInMinutes($checkOutTime);

            $attendance->update([
                'check_out_at' => $checkOutTime,
                'duration_minutes' => $durationMinutes,
                'status' => 'completed'
            ]);

            return $attendance;
        });
    }
}
