<?php

namespace Modules\Core\DTOs;

use Modules\Core\Enums\Gender;

readonly class CreatePersonDTO
{
    public function __construct(
        public string $fullName,
        public string $mobile1,
        public ?Gender $gender = null,
        public ?string $dob = null,
        public ?string $type = null,
        public ?string $email = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'full_name' => $this->fullName,
            'mobile_1' => $this->mobile1,
            'gender' => $this->gender?->value,
            'dob' => $this->dob,
            'type' => $this->type,
            'email' => $this->email,
        ], fn($value) => !is_null($value));
    }
}
