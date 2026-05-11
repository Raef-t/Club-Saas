<?php

namespace Modules\BranchManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LockerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'facility_id' => $this->facility_id,
            'locker_number' => $this->locker_number,
            'is_active' => $this->is_active,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'facility' => new FacilityResource($this->whenLoaded('facility')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
