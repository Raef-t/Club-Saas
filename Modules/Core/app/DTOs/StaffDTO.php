<?php

namespace Modules\Core\DTOs;

readonly class StaffDTO
{
    public function __construct(
        public int $id,
        public int $personId,
        public ?int $branchId,
        public string $employmentType,
        public bool $isActive,
        public ?PersonDTO $person = null
    ) {}
}
