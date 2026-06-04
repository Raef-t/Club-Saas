<?php

namespace Modules\AttendanceManager\DTOs;

use Carbon\Carbon;

class CheckOutAttempt
{
    public function __construct(
        public readonly int $clubId,
        public readonly string $attendableType,
        public readonly int $attendableId,
        public readonly int $branchId,
        public readonly Carbon $timestamp,
        public readonly array $metadata = []
    ) {}
}
