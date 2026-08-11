<?php

namespace Modules\AttendanceManager\DTOs;

class CheckInAttempt
{
    public function __construct(
        public readonly string $attendableType,
        public readonly int|string $attendableId,
        public readonly int|string $clubId,
        public readonly int|string $branchId,
        public readonly \DateTimeImmutable $timestamp
    ) {}
}
