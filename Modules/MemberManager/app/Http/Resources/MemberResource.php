<?php

namespace Modules\MemberManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BranchManager\Http\Resources\BranchResource;

class MemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'member_number' => $this->member_number,
            'membership_status' => $this->membership_status,
            'join_date' => $this->join_date,
            'person' => [
                'full_name' => $this->person->full_name ?? null,
                'email' => $this->person->email ?? null,
                'phone' => $this->person->phone ?? null,
            ],
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'health_profile' => $this->whenLoaded('healthProfile'),
            'measurements' => $this->whenLoaded('measurements'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
