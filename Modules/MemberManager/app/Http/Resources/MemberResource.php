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
            ] : null,
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
