<?php

namespace Modules\MemberManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "MemberResource",
    title: "Member Resource",
    description: "Member resource representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "member_number", type: "string", example: "MEM-2023-0001"),
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "membership_status", type: "string", example: "active"),
        new OA\Property(property: "join_date", type: "string", format: "date", example: "2023-01-01"),
        new OA\Property(property: "person", type: "object"),
        new OA\Property(property: "generated_username", type: "string", nullable: true),
        new OA\Property(property: "generated_password", type: "string", nullable: true),
        new OA\Property(property: "branch", type: "object", nullable: true),
        new OA\Property(property: "health_profile", type: "object", nullable: true),
        new OA\Property(property: "measurements", type: "array", items: new OA\Items(type: "object"), nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time")
    ]
)]
class MemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'member_number' => $this->member_number,
            'branch_id' => $this->branch_id,
            'membership_status' => $this->membership_status,
            'join_date' => $this->join_date,
            'person' => $this->person ? [
                'full_name' => $this->person->fullName,
                'email' => $this->person->email,
                'country_code' => $this->person->mobile1CountryCode,
                'phone_number' => $this->person->mobile1,
                'gender' => $this->person->gender,
                'age' => $this->person->age,
                'dob' => $this->person->dob,
                'national_id' => $this->person->nationalId,
                'social_status' => $this->person->socialStatus,
                'address' => $this->person->address,
                'photo_url' => $this->person->photoUrl,
                'secondary_country_code' => $this->person->mobile2CountryCode,
                'secondary_phone_number' => $this->person->mobile2,
                'landline' => $this->person->landline,
                'emergency_contact_name' => $this->person->emergencyContactName,
                'emergency_contact_country_code' => $this->person->emergencyContactCountryCode,
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
                'gender_restriction' => $this->branch->genderRestriction ? $this->branch->genderRestriction->value : null,
                'is_active' => $this->branch->isActive,
            ] : null,
            'health_profile' => $this->healthProfile,
            'measurements' => $this->measurements,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
