<?php

namespace Modules\AttendanceManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AttendanceManager\Models\StaffAttendance;

class StaffCheckedOut
{
    use Dispatchable, SerializesModels;

    public $attendance;

    public function __construct(StaffAttendance $attendance)
    {
        $this->attendance = $attendance;
    }
}
