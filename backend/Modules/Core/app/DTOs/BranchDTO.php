<?php

namespace Modules\Core\DTOs;

use Modules\Core\Enums\Gender;

readonly class BranchDTO
{
    public function __construct(
        public int $id,
        public array $name, // Translatable json array: {"ar": "...", "en": "..."}
        public Gender $genderRestriction,
        public bool $isActive
    ) {}
}
