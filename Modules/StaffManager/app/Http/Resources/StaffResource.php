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
            'base_salary' => $this->base_salary,
            'contract_type' => $this->contract_type,
            'shift_type' => $this->shift_type,
            'work_type' => $this->work_type,
            'work_status' => $this->work_status,
            'other_tasks' => $this->other_tasks,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_active' => $this->is_active,

            // Coach-specific details (only present when role = coach)
            'coach_details' => $this->when($this->isCoach() && $this->relationLoaded('coachDetail'), function () {
                $detail = $this->coachDetail;
                if (!$detail) return null;

                return [
                    'specialization'          => $detail->specialization,
                    'bio'                     => $detail->bio,
                    'experience_years'        => $detail->experience_years,
                    'payment_type'            => $detail->payment_type,
                    'commission_type'         => $detail->commission_type,
                    'default_commission_rate' => $detail->default_commission_rate,
                    'working_hours_per_week'  => $detail->working_hours_per_week,
                    'gym_type'                => $detail->gym_type,
                    'certifications'          => $detail->relationLoaded('certifications')
                        ? $detail->certifications->map(fn($cert) => [
                            'id'           => $cert->id,
                            'name'         => $cert->name,
                            'issuer'       => $cert->issuer,
                            'issue_date'   => $cert->issue_date?->toDateString(),
                            'expiry_date'  => $cert->expiry_date?->toDateString(),
                            'document_url' => $cert->document_url,
                            'is_expired'   => $cert->isExpired(),
                        ])
                        : [],
                ];
            }),

            // Person data (resolved via DTO)
            'person' => $this->person ? [
                'full_name' => $this->person->fullName,
                'mobile_1_country_code' => $this->person->mobile1CountryCode,
                'mobile_1' => $this->person->mobile1,
                'email' => $this->person->email,
                'gender' => $this->person->gender?->value,
                'dob' => $this->person->dob,
                'national_id' => $this->person->nationalId,
                'social_status' => $this->person->socialStatus,
                'address' => $this->person->address,
                'photo_url' => $this->person->photoUrl,
                'mobile_2_country_code' => $this->person->mobile2CountryCode,
                'mobile_2' => $this->person->mobile2,
                'landline' => $this->person->landline,
                'emergency_contact_name' => $this->person->emergencyContactName,
                'emergency_contact_country_code' => $this->person->emergencyContactCountryCode,
                'emergency_contact_phone' => $this->person->emergencyContactPhone,
                'chronic_diseases' => $this->person->chronicDiseases,
                'children_count' => $this->person->childrenCount,
                'how_did_you_hear' => $this->person->howDidYouHear,
                'notes' => $this->person->notes,
            ] : null,

            'generated_username' => $this->generated_username ?? null,
            'generated_password' => $this->generated_password ?? null,
            'branch_name' => $this->branch->name ?? null,
            'shifts' => $this->whenLoaded('shifts'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
