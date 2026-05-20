<?php

namespace Modules\Core\DTOs;

use Modules\Core\Enums\Gender;

readonly class UpdatePersonDTO
{
    public function __construct(
        public ?string $fullName = null,
        public ?string $mobile1 = null,
        public ?Gender $gender = null,
        public ?string $dob = null,
        public ?string $email = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'full_name' => $this->fullName,
            'mobile_1' => $this->mobile1,
            'gender' => $this->gender?->value,
            'dob' => $this->dob,
            'email' => $this->email,
        ], fn($value) => !is_null($value));
    }
}
