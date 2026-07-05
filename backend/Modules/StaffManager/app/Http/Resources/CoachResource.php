<?php

namespace Modules\StaffManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CoachResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'person_id'       => $this->person_id,
            'branch_ids'      => $this->whenLoaded('branches', fn() => $this->branches->pluck('id')),
            'role'            => $this->role,
            'employment_type' => $this->employment_type,
            'base_salary'     => $this->base_salary,
            'is_active'       => $this->is_active,
            'start_date'      => $this->start_date,
            'end_date'        => $this->end_date,
            'contract_type'   => $this->contract_type,
            'shift_type'      => $this->shift_type,
            'work_type'       => $this->work_type,
            'work_status'     => $this->work_status,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            
            // Relations
            'person'         => $this->whenLoaded('person'),
            'username'       => $this->whenLoaded('user', fn() => $this->user ? $this->user->username : null),
            'details'        => $this->whenLoaded('coachDetail'),
            'activities'     => $this->whenLoaded('activities'),
        ];
    }
}
