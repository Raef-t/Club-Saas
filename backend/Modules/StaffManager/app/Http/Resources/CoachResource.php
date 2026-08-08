<?php

namespace Modules\StaffManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sports\Http\Resources\ActivityResource;
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
        new OA\Property(property: "start_date", type: "string", format: "date", example: "2023-01-01"),
        new OA\Property(property: "end_date", type: "string", format: "date", nullable: true),
        new OA\Property(property: "start_time", type: "string", format: "time", example: "09:00", nullable: true),
        new OA\Property(property: "end_time", type: "string", format: "time", example: "17:00", nullable: true),
        new OA\Property(property: "experience_years", type: "integer"),
        new OA\Property(property: "payment_type", type: "string", nullable: true),
        new OA\Property(property: "work_types", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(
            property: "work_status",
            type: "string",
            enum: ["active", "suspended", "on_leave"],
            example: "active",
            description: "حالة العمل (active: نشط، suspended: موقوف، on_leave: إجازة)"
        ),
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
        $contract = $this->activeContract;
        $detail = $this->coachDetail;
        $todayQrCode = $this->person_id 
            ? app(\Modules\Authentication\Services\PersonQrCodeService::class)->getTodayCodeForPerson($this->person_id) 
            : null;

        return [
            'id'              => $this->id,
            'person_id'       => $this->person_id,
            'qr_code'         => $todayQrCode,
            'branch_ids'      => $this->branches->pluck('id'),
            'role'            => $this->role,
            'employment_type' => $contract ? $contract->employment_type : null,
            'base_salary'     => $contract ? $contract->base_salary : 0,
            'start_date'      => $this->start_date?->toDateString() ?? $contract?->start_date?->toDateString() ?? $this->created_at?->toDateString(),
            'end_date'        => $this->end_date?->toDateString() ?? $contract?->end_date?->toDateString(),
            'start_time'      => $this->start_time,
            'end_time'        => $this->end_time,
            'work_status'     => $this->work_status,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            
            // Relations
            'person'         => $this->person ? [
                'id'           => $this->person->id,
                'full_name'    => $this->person->full_name,
                'gender'       => $this->person->gender,
                'age'          => $this->person->age,
                'dob'          => $this->person->dob,
                'address'      => $this->person->address,
                'photo_url'    => $this->person->photo_url,
                'email'        => $this->person->email,
                'phone_number' => $this->person->contacts->where('name', 'Personal')->first()?->phone_number,
                'country_code' => $this->person->contacts->where('name', 'Personal')->first()?->country_code,
            ] : null,
            'username'       => $this->user ? $this->user->username : null,
            'details'        => $detail ? [
                'id' => $detail->id,
                'staff_id' => $detail->staff_id,
                'bio' => $detail->bio,
                'experience_years' => $detail->experience_years,
                'gym_type' => $detail->gym_type,
                'work_types' => $detail->work_types,
                'working_hours_per_week' => $detail->working_hours_per_week ?? null,
                'payment_type' => $contract ? $contract->employment_type : null,
                'commission_type' => $contract ? $contract->commission_type : null,
                'default_commission_rate' => $contract ? $contract->commission_rate : 0,
                'created_at' => $detail->created_at,
                'updated_at' => $detail->updated_at,
            ] : null,
            'activities'     => ActivityResource::collection($this->activities),
            'experience_years'       => $detail?->experience_years,
            'work_types'     => $detail?->work_types,
            'shifts'         => $this->shifts->map(fn($shift) => [
                'id'              => $shift->id,
                'branch_shift_id' => $shift->branch_shift_id,
                'name'            => $shift->branchShift?->name,
                'start_time'      => $shift->branchShift?->start_time,
                'end_time'        => $shift->branchShift?->end_time,
            ]),
        ];
    }
}
