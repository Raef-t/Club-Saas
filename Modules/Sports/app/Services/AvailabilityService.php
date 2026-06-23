<?php

namespace Modules\Sports\Services;

use Modules\Sports\Models\SportSession;
use Modules\StaffManager\Models\StaffWorkingHour;
use Modules\StaffManager\Models\StaffLeave;
use Modules\ClubManager\Models\FacilityWorkingHour;
use Carbon\Carbon;
use Exception;

class AvailabilityService
{
    /**
     * Check if the staff member is available at the requested time.
     */
    public function checkStaffAvailability($staffId, Carbon $startTime, Carbon $endTime)
    {
        // 1. Check Working Hours
        $dayOfWeek = $startTime->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        
        $workingHour = StaffWorkingHour::where('staff_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$workingHour) {
            throw new Exception(__("Staff member does not work on this day."));
        }

        $startTimeStr = $startTime->format('H:i:s');
        $endTimeStr = $endTime->format('H:i:s');

        if ($startTimeStr < $workingHour->start_time || $endTimeStr > $workingHour->end_time) {
            throw new Exception(__("Requested time is outside staff working hours."));
        }

        // 2. Check Leaves/Vacations
        $date = $startTime->format('Y-m-d');
        $onLeave = StaffLeave::where('staff_id', $staffId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($onLeave) {
            throw new Exception(__("Staff member is on leave during this date."));
        }

        // 3. Check for Overlapping Sessions
        $overlappingSession = SportSession::where('staff_id', $staffId)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })->exists();

        if ($overlappingSession) {
            throw new Exception(__("Staff member already has a session at this time."));
        }

        return true;
    }

    /**
     * Check if the facility is available at the requested time.
     */
    public function checkFacilityAvailability($facilityId, Carbon $startTime, Carbon $endTime)
    {
        // 1. Check Working Hours
        $dayOfWeek = $startTime->dayOfWeek;
        $workingHour = FacilityWorkingHour::where('facility_id', $facilityId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$workingHour) {
            throw new Exception(__("Facility is closed on this day."));
        }

        $startTimeStr = $startTime->format('H:i:s');
        $endTimeStr = $endTime->format('H:i:s');

        if ($startTimeStr < $workingHour->open_time || $endTimeStr > $workingHour->close_time) {
            throw new Exception(__("Requested time is outside facility working hours."));
        }

        // 2. Check for Overlapping Sessions in this facility
        $overlappingSession = SportSession::where('facility_id', $facilityId)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })->exists();

        if ($overlappingSession) {
            throw new Exception(__("Facility is already booked for this time."));
        }

        return true;
    }
}
