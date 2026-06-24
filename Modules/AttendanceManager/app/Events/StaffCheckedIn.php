<?php

namespace Modules\AttendanceManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AttendanceManager\Models\Attendance;

class StaffCheckedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Attendance $attendance) {}
}
