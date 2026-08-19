<?php

namespace Modules\AttendanceManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'attendable_type'  => $this->attendable_type,
            'attendable_id'    => $this->attendable_id,
            // Convenience fields depending on type
            'member_id'        => $this->attendable_type === 'member' ? $this->attendable_id : null,
            'staff_id'         => $this->attendable_type === 'staff'  ? $this->attendable_id : null,
            // The staff member (receptionist) who recorded this check-in
            'recorded_by_staff_id' => $this->recorded_by_staff_id,
            'branch_id'        => $this->branch_id,
            'locker_id'        => $this->locker_id,
            'locker_number'    => $this->locker?->locker_number,
            'locker'           => $this->locker ? [
                'id'            => $this->locker->id,
                'locker_number' => $this->locker->locker_number,
                'key_number'    => $this->locker->key_number ?? null,
            ] : null,
            'check_in'         => $this->check_in_at?->toIso8601String(),
            'check_out'        => $this->check_out_at?->toIso8601String(),
            'duration_minutes'   => $this->duration_minutes ?? null,
            'duration_formatted' => $this->formatted_duration,
            'status'           => $this->status,
            'notes'            => $this->notes,
            'consumptions'     => $this->consumptions ? $this->consumptions->map(function ($consumption) {
                return [
                    'id'                     => $consumption->id,
                    'subscription_plan_id'   => $consumption->subscription_plan_id,
                    'subscription_plan_name' => $consumption->subscriptionPlan?->name,
                ];
            }) : [],
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
