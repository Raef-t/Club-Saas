<?php

namespace Modules\AttendanceManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AttendanceManager\Models\MemberAttendance;

class MemberCheckedOut
{
    use Dispatchable, SerializesModels;

    public $attendance;

    public function __construct(MemberAttendance $attendance)
    {
        $this->attendance = $attendance;
    }
}
