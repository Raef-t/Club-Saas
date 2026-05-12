<?php

namespace Modules\StaffManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'employment_type' => $this->employment_type,
            'specialization' => $this->specialization,
            'base_salary' => $this->base_salary,
            'person' => [
                'full_name' => $this->person->full_name ?? null,
                'mobile_1' => $this->person->mobile_1 ?? null,
                'email' => $this->person->email ?? null,
            ],
            'branch_name' => $this->branch->name ?? null,
            'is_active' => $this->is_active,
            'shifts' => $this->whenLoaded('shifts'),
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
