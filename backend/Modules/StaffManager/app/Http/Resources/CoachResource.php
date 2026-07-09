<?php

namespace Modules\StaffManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CoachResource",
    title: "Coach Resource",
    description: "Coach resource representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "person_id", type: "integer", example: 10),
        new OA\Property(property: "branch_ids", type: "array", items: new OA\Items(type: "integer", example: 1)),
        new OA\Property(property: "role", type: "string", example: "coach"),
        new OA\Property(property: "employment_type", type: "string", example: "fixed_salary"),
        new OA\Property(property: "base_salary", type: "number", example: 5000),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "start_date", type: "string", format: "date", example: "2023-01-01"),
        new OA\Property(property: "end_date", type: "string", format: "date", nullable: true),
        new OA\Property(property: "experience_years", type: "integer"),
        new OA\Property(property: "payment_type", type: "string", nullable: true),
        new OA\Property(property: "contract_type", type: "string", nullable: true),
        new OA\Property(property: "shift_type", type: "string", nullable: true),
        new OA\Property(property: "work_types", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "work_status", type: "string", example: "active"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
        new OA\Property(property: "person", type: "object", description: "Person details"),
        new OA\Property(property: "username", type: "string", nullable: true, example: "coach_123"),
        new OA\Property(property: "details", type: "object", description: "Coach specific details", properties: [
            new OA\Property(property: "default_commission_rate", type: "number", format: "float", example: 15.5, description: "نسبة العمولة الثابتة (مئوية)")
        ]),
        new OA\Property(property: "activities", type: "array", items: new OA\Items(type: "object"), description: "Assigned activities"),
    ]
)]
class CoachResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'person_id'       => $this->person_id,
            'branch_ids'      => $this->branches->pluck('id'),
            'role'            => $this->role,
            'employment_type' => $this->employment_type,
            'base_salary'     => $this->base_salary,
            'is_active'       => $this->is_active,
            'start_date'      => $this->start_date,
            'end_date'        => $this->end_date,
            'contract_type'   => $this->contract_type,
            'shift_type'      => $this->shift_type,
            'work_types'      => $this->work_types,
            'work_status'     => $this->work_status,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            
            // Relations
            'person'         => $this->person,
            'username'       => $this->user ? $this->user->username : null,
            'details'        => $this->coachDetail,
            'activities'     => $this->activities,
            'experience_years'       => $this->coachDetail?->experience_years,
        ];
    }
}
