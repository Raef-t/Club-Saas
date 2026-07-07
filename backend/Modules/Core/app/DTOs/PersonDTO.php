<?php

namespace Modules\Core\DTOs;

use Modules\Core\Enums\Gender;

readonly class PersonDTO
{
    public function __construct(
        public int $id,
        public string $fullName,
        public ?Gender $gender,
        public string $mobile1,
        public ?string $mobile1CountryCode = null,
        public ?string $email = null,
        public ?string $nationalId = null,
        public ?string $socialStatus = null,
        public ?string $address = null,
        public ?string $photoUrl = null,
        public ?string $mobile2 = null,
        public ?string $mobile2CountryCode = null,
        public ?string $landline = null,
        public ?string $emergencyContactName = null,
        public ?string $emergencyContactPhone = null,
        public ?string $emergencyContactCountryCode = null,
        public ?string $chronicDiseases = null,
        public ?int $childrenCount = null,
        public ?string $howDidYouHear = null,
        public ?string $notes = null,
        public ?int $age = null,
        public ?string $dob = null
    ) {}
}
