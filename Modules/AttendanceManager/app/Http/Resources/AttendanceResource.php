<?php

namespace Modules\AttendanceManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        $metadata = $this->metadata ?? [];
        
        $staffId = $this->attendable_type === 'staff' ? $this->attendable_id : null;
        $memberId = $this->attendable_type === 'player_subscription' ? ($metadata['member_id'] ?? null) : null;
        $facilityId = $metadata['facility_id'] ?? $this->branch_id;

        return [
            'id' => $this->id,
            'staff_id' => $staffId,
            'member_id' => $memberId,
            'branch_id' => $this->branch_id,
            'facility_id' => $facilityId,
            'check_in' => $this->check_in_at?->toIso8601String() ?? $this->check_in_at,
            'check_out' => $this->check_out_at?->toIso8601String() ?? $this->check_out_at,
            'status' => $this->status,
            'notes' => $metadata['notes'] ?? null,
            'metadata' => $metadata,
            'created_at' => $this->created_at?->toIso8601String() ?? $this->created_at,
            'updated_at' => $this->updated_at?->toIso8601String() ?? $this->updated_at,
        ];
    }
}
