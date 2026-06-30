<?php

namespace Modules\Core\DTOs;

readonly class StaffDTO
{
    public function __construct(
        public int $id,
        public int $personId,
        public ?int $branchId,
        public string $role,
        public string $employmentType,
        public bool $isActive,
        public ?string $specialization = null,
        public ?PersonDTO $person = null
    ) {}
}
