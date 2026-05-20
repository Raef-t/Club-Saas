<?php

namespace Modules\Core\DTOs;

readonly class MemberDTO
{
    public function __construct(
        public int $id,
        public int $personId,
        public ?int $branchId,
        public string $memberNumber,
        public string $barcode,
        public string $status, // e.g. active, frozen, expired
        public bool $isActive,
        public ?PersonDTO $person = null
    ) {}
}
