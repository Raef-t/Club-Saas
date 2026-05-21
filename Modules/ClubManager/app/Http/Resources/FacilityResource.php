<?php

namespace Modules\ClubManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FacilityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'gender_restriction' => $this->gender_restriction,
            'is_active' => $this->is_active,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
