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
        public ?string $email = null,
        public ?string $nationalId = null,
        public ?string $socialStatus = null,
        public ?string $address = null,
        public ?string $photoUrl = null,
        public ?string $mobile2 = null,
        public ?string $landline = null,
        public ?string $emergencyContactName = null,
        public ?string $emergencyContactPhone = null,
        public ?string $chronicDiseases = null,
        public ?int $childrenCount = null,
        public ?string $howDidYouHear = null,
        public ?string $notes = null
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
            'national_id' => $this->nationalId,
            'social_status' => $this->socialStatus,
            'address' => $this->address,
            'photo_url' => $this->photoUrl,
            'mobile_2' => $this->mobile2,
            'landline' => $this->landline,
            'emergency_contact_name' => $this->emergencyContactName,
            'emergency_contact_phone' => $this->emergencyContactPhone,
            'chronic_diseases' => $this->chronicDiseases,
            'children_count' => $this->childrenCount,
            'how_did_you_hear' => $this->howDidYouHear,
            'notes' => $this->notes,
        ], fn($value) => !is_null($value));
    }
}
