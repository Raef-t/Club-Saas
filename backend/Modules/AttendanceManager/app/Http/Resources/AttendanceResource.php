<?php

namespace Modules\AttendanceManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        $metadata = $this->metadata ?? [];

        // Resolve locker number if a locker is assigned
        $lockerNumber = null;
        if ($this->locker_id) {
            $locker = DB::table('lockers')->where('id', $this->locker_id)->first();
            $lockerNumber = $locker?->locker_number;
        }

        return [
            'id'               => $this->id,
            'attendable_type'  => $this->attendable_type,
            'attendable_id'    => $this->attendable_id,
            // Convenience fields depending on type
            'member_id'        => $this->attendable_type === 'member' ? $this->attendable_id : null,
            'staff_id'         => $this->attendable_type === 'staff'  ? $this->attendable_id : null,
            // The staff member (receptionist) who recorded this check-in
            'recorded_by_staff_id' => $this->recorded_by_staff_id,
            // Subscription reference stored in metadata for members
            'subscription_id'  => $metadata['subscription_id'] ?? null,
            'club_id'          => $this->club_id,
            'branch_id'        => $this->branch_id,
            'facility_id'      => $metadata['facility_id'] ?? null,
            // Locker key details
            'locker_id'        => $this->locker_id,
            'locker_number'    => $lockerNumber,
            'locker_holder_name' => $this->locker_holder_name,
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
