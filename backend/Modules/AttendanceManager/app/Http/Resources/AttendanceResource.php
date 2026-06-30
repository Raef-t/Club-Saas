<?php

namespace Modules\AttendanceManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        $metadata = $this->metadata ?? [];

        return [
            'id'               => $this->id,
            'attendable_type'  => $this->attendable_type,
            'attendable_id'    => $this->attendable_id,
            // Convenience fields depending on type
            'member_id'        => $this->attendable_type === 'member' ? $this->attendable_id : null,
            'staff_id'         => $this->attendable_type === 'staff'  ? $this->attendable_id : null,
            // Subscription reference stored in metadata for members
            'subscription_id'  => $metadata['subscription_id'] ?? null,
            'club_id'          => $this->club_id,
            'branch_id'        => $this->branch_id,
            'facility_id'      => $metadata['facility_id'] ?? null,
            'check_in'         => $this->check_in_at?->toIso8601String(),
            'check_out'        => $this->check_out_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes ?? null,
            'status'           => $this->status,
            'metadata'         => $metadata,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
