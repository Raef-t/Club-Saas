<?php

namespace Modules\AttendanceManager\Contracts;

use Modules\AttendanceManager\DTOs\CheckInAttempt;
use Modules\AttendanceManager\DTOs\AttendanceDecision;

interface AttendancePolicy
{
    /**
     * Determine if the check-in attempt is authorized by the module's business rules.
     *
     * @param CheckInAttempt $attempt
     * @return AttendanceDecision
     */
    public function authorize(CheckInAttempt $attempt): AttendanceDecision;
}
