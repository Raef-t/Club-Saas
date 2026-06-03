<?php

namespace Modules\MemberManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'member_number' => $this->member_number,
            'membership_status' => $this->membership_status,
            'join_date' => $this->join_date,
            'person' => $this->person ? [
                'full_name' => $this->person->fullName,
                'email' => $this->person->email,
                'phone' => $this->person->mobile1,
                'gender' => $this->person->gender?->value,
                'dob' => $this->person->dob,
                'national_id' => $this->person->nationalId,
                'social_status' => $this->person->socialStatus,
                'address' => $this->person->address,
                'photo_url' => $this->person->photoUrl,
                'mobile_2' => $this->person->mobile2,
                'landline' => $this->person->landline,
                'emergency_contact_name' => $this->person->emergencyContactName,
                'emergency_contact_phone' => $this->person->emergencyContactPhone,
                'chronic_diseases' => $this->person->chronicDiseases,
                'children_count' => $this->person->childrenCount,
                'how_did_you_hear' => $this->person->howDidYouHear,
                'notes' => $this->person->notes,
            ] : null,
            'generated_username' => $this->generated_username ?? null,
            'generated_password' => $this->generated_password ?? null,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'gender_restriction' => $this->branch->genderRestriction->value,
                'is_active' => $this->branch->isActive,
            ] : null,
            'health_profile' => $this->whenLoaded('healthProfile'),
            'measurements' => $this->whenLoaded('measurements'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
