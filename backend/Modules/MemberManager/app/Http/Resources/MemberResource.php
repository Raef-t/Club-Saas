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
        $todayQrCode = $this->person_id 
            ? app(\Modules\Authentication\Services\PersonQrCodeService::class)->getTodayCodeForPerson($this->person_id) 
            : null;

        return [
            'id' => $this->id,
            'member_number' => $this->member_number,
            'qr_code' => $todayQrCode,
            'branch_id' => $this->branch_id,
            'membership_status' => $this->membership_status,
            'join_date' => $this->join_date,
            'person' => $this->person ? [
                'full_name' => $this->person->full_name,
                'email' => $this->person->email,
                'gender' => $this->person->gender,
                'age' => $this->person->age,
                'dob' => $this->person->dob,
                'national_id' => $this->person->national_id,
                'social_status' => $this->person->social_status,
                'address' => $this->person->address,
                'photo_url' => $this->person->photo_url,
                'chronic_diseases' => $this->person->chronic_diseases,
                'children_count' => $this->person->children_count,
                'how_did_you_hear' => $this->person->how_did_you_hear,
                'notes' => $this->person->notes,
                'contacts' => $this->person->contacts->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'country_code' => $contact->country_code,
                        'phone_number' => $contact->phone_number,
                        'relation' => $contact->relation,
                    ];
                }),
            ] : null,
            'generated_username' => $this->generated_username ?? $this->person?->user?->username ?? null,
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
