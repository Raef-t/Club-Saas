<?php

namespace Modules\StaffManager\Listeners;

use Modules\AttendanceManager\Events\CheckInRecorded;
use Illuminate\Support\Facades\Log;

class CalculateStaffWorkHours
{
    /**
     * Handle the event.
     *
     * @param CheckInRecorded $event
     * @return void
     */
    public function handle(CheckInRecorded $event): void
    {
        // Only react to staff attendance records
        if ($event->attendance->attendable_type !== 'staff') {
            return;
        }

        $attendance = $event->attendance;

        Log::info("Staff check-in recorded for Staff ID: {$attendance->attendable_id}. Calculating shifts / timesheet hours.");

        // Here you would implement shifts updating or hours calculations:
        // $staff = $attendance->attendable;
        // ... timesheet calculations ...
    }
}
