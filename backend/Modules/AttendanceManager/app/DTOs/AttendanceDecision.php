<?php

namespace Modules\AttendanceManager\DTOs;

class AttendanceDecision
{
    private function __construct(
        public readonly bool $isAllowed,
        public readonly ?string $rejectionReason = null,
        public readonly array $context = []
    ) {}

    public static function allow(array $context = []): self
    {
        return new self(true, null, $context);
    }

    public static function deny(string $rejectionReason, array $context = []): self
    {
        return new self(false, $rejectionReason, $context);
    }
}
