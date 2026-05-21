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
            'commission_rate' => $this->commission_rate,
            'contract_type' => $this->contract_type,
            'work_type' => $this->work_type,
            'work_status' => $this->work_status,
            'salary_type' => $this->salary_type,
            'employee_type' => $this->employee_type,
            'other_tasks' => $this->other_tasks,
            'gym_type' => $this->gym_type,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'person' => [
                'full_name' => $this->person->full_name ?? null,
                'mobile_1' => $this->person->mobile_1 ?? null,
                'email' => $this->person->email ?? null,
            ],
            'branch_name' => $this->branch->name ?? null,
            'is_active' => $this->is_active,
            'shifts' => $this->whenLoaded('shifts'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
