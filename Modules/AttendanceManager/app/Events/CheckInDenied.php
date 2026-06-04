<?php

namespace Modules\AttendanceManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\AttendanceManager\DTOs\CheckInAttempt;
use Modules\AttendanceManager\DTOs\AttendanceDecision;

class CheckInDenied
{
    use Dispatchable;

    public function __construct(
        public readonly CheckInAttempt $attempt,
        public readonly AttendanceDecision $decision
    ) {}
}
