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
            'person' => $this->person ? [
                'full_name' => $this->person->fullName,
                'mobile_1' => $this->person->mobile1,
                'email' => $this->person->email,
                'gender' => $this->person->gender?->value,
                'dob' => $this->person->dob,
                'national_id' => $this->person->nationalId,
                'social_status' => $this->person->socialStatus,
                'address' => $this->person->address,
                'photo_url' => $this->person->photoUrl,
                'mobile_2' => $this->person->mobile2,
                'landline' => $this->person->landline,
                'emergency_contact_name' => $this->person->emergencyContactName,
                'emergency_contact_phone' => $this->person->emergencyContactPhone,
                'chronic_diseases' => $this->person->chronicDiseases,
                'children_count' => $this->person->childrenCount,
                'how_did_you_hear' => $this->person->howDidYouHear,
                'notes' => $this->person->notes,
            ] : null,
            'generated_username' => $this->generated_username ?? null,
            'generated_password' => $this->generated_password ?? null,
            'branch_name' => $this->branch->name ?? null,
            'is_active' => $this->is_active,
            'shifts' => $this->whenLoaded('shifts'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
