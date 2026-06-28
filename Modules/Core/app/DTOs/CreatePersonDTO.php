<?php

namespace Modules\Core\DTOs;

use Modules\Core\Enums\Gender;

readonly class CreatePersonDTO
{
    public function __construct(
        public string $fullName,
        public string $mobile1,
        public ?string $mobile1CountryCode = null,
        public ?Gender $gender = null,
        public ?string $dob = null,
        public ?string $type = null,
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
        public ?string $notes = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'full_name' => $this->fullName,
            'gender' => $this->gender?->value,
            'dob' => $this->dob,
            'type' => $this->type,
            'email' => $this->email,
            'national_id' => $this->nationalId,
            'social_status' => $this->socialStatus,
            'address' => $this->address,
            'photo_url' => $this->photoUrl,
            'chronic_diseases' => $this->chronicDiseases,
            'children_count' => $this->childrenCount,
            'how_did_you_hear' => $this->howDidYouHear,
            'notes' => $this->notes,
        ], fn($value) => !is_null($value));
    }
}
