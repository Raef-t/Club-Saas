<?php

namespace Modules\StaffManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sports\Http\Resources\ActivityResource;
use Modules\StaffManager\Http\Resources\CoachActivityResource;
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
        new OA\Property(property: "reason", type: "string", nullable: true, description: "آخر سبب للتعديل", example: "تحديث الراتب والفرع"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
        new OA\Property(property: "person", type: "object", description: "Person details"),
        new OA\Property(property: "username", type: "string", nullable: true, example: "coach_123"),
        new OA\Property(property: "details", type: "object", description: "Coach specific details", properties: [
            new OA\Property(property: "default_commission_rate", type: "number", format: "float", example: 15.5, description: "نسبة العمولة الثابتة (مئوية)"),
            new OA\Property(property: "private_commission_rate", type: "number", format: "float", example: 70.0, description: "نسبة الكوتش من اشتراكات أجهزة خاص (مئوية)")
        ]),
        new OA\Property(
            property: "activities",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/CoachActivityResource"),
            description: "الأنشطة المنسوبة للمدرب مع تفاصيل الشفتات أو المواعيد"
        ),
        new OA\Property(
            property: "shifts",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/CoachActivityShiftItem"),
            description: "شفتات المدرب المباشرة"
        ),
    ],
    example: [
        "id" => 5,
        "person_id" => 12,
        "qr_code" => "data:image/png;base64,iVBORw0KGgo...",
        "branch_ids" => [1],
        "role" => "coach",
        "employment_type" => "hybrid",
        "base_salary" => 4500,
        "start_date" => "2024-01-01",
        "end_date" => null,
        "start_time" => "08:00",
        "end_time" => "16:00",
        "work_status" => "active",
        "reason" => "تحديث بيانات الراتب والشفت",
        "created_at" => "2024-01-01T08:00:00.000000Z",
        "updated_at" => "2024-06-15T10:30:00.000000Z",
        "person" => [
            "id" => 12,
            "full_name" => "الكابتن أحمد علي",
            "gender" => "male",
            "age" => 31,
            "dob" => "1995-05-14",
            "address" => "الرياض - حي الملز",
            "photo_url" => "https://api.domain.com/storage/people/photos/coach_5.jpg",
            "email" => "ahmed.coach@example.com",
            "phone_number" => "0501234567",
            "country_code" => "+966"
        ],
        "username" => "coach_ahmed",
        "details" => [
            "id" => 5,
            "staff_id" => 5,
            "bio" => "مدرب كمال أجسام ولياقة بدنية وفنون قتالية معتمد دولياً",
            "experience_years" => 7,
            "gym_type" => "male",
            "work_types" => ["equipment", "activities"],
            "working_hours_per_week" => 40,
            "payment_type" => "hybrid",
            "commission_type" => "percentage",
            "default_commission_rate" => 20.0,
            "private_commission_rate" => 70.0,
            "created_at" => "2024-01-01T08:00:00.000000Z",
            "updated_at" => "2024-06-15T10:30:00.000000Z"
        ],
        "experience_years" => 7,
        "work_types" => ["equipment", "activities"],
        "shifts" => [
            [
                "id" => 10,
                "branch_shift_id" => 2,
                "name" => "الشفت الصباحي",
                "start_time" => "08:00",
                "end_time" => "16:00"
            ]
        ],
        "activities" => [
            [
                "id" => 1,
                "name" => "تدريب عام وأجهزة",
                "branch_id" => 1,
                "activity_type" => [
                    "id" => 1,
                    "name" => "تدريب عام",
                    "is_active" => true,
                    "is_session_based" => false,
                    "has_unlimited_subscribers" => true,
                    "has_shifts" => true,
                    "is_daily_entry" => false
                ],
                "is_unlimited_subscribers" => true,
                "description" => "تدريب كمال الأجسام واللياقة العامة في صالة الأجهزة",
                "is_private_equipment" => false,
                "is_active" => true,
                "created_at" => "2024-01-01T08:00:00.000000Z",
                "schedule_type" => "shifts",
                "shifts" => [
                    [
                        "id" => 10,
                        "branch_shift_id" => 2,
                        "name" => "الشفت الصباحي",
                        "start_time" => "08:00",
                        "end_time" => "16:00"
                    ]
                ],
                "schedules" => []
            ],
            [
                "id" => 2,
                "name" => "تدريب خاص (شخصي)",
                "branch_id" => 1,
                "activity_type" => [
                    "id" => 2,
                    "name" => "تدريب خاص",
                    "is_active" => true,
                    "is_session_based" => false,
                    "has_unlimited_subscribers" => true,
                    "has_shifts" => false,
                    "is_daily_entry" => false
                ],
                "is_unlimited_subscribers" => true,
                "description" => "جلسات تدريب شخصي 1-on-1 بالاتفاق",
                "is_private_equipment" => true,
                "is_active" => true,
                "created_at" => "2024-01-01T08:00:00.000000Z",
                "schedule_type" => "none",
                "shifts" => [],
                "schedules" => []
            ],
            [
                "id" => 3,
                "name" => "كيك بوكسينغ",
                "branch_id" => 1,
                "activity_type" => [
                    "id" => 3,
                    "name" => "حصة جماعية",
                    "is_active" => true,
                    "is_session_based" => true,
                    "has_unlimited_subscribers" => false,
                    "has_shifts" => false,
                    "is_daily_entry" => false
                ],
                "is_unlimited_subscribers" => false,
                "description" => "حصص كيك بوكسينغ جماعية أسبوعية",
                "is_private_equipment" => false,
                "is_active" => true,
                "created_at" => "2024-01-01T08:00:00.000000Z",
                "schedule_type" => "schedule",
                "shifts" => [],
                "schedules" => [
                    [
                        "id" => 15,
                        "plan_id" => 4,
                        "plan_name" => "اشتراك كيك بوكسينغ شهري",
                        "day_of_week" => 0,
                        "day_name" => "Sunday",
                        "day_name_ar" => "الأحد",
                        "start_time" => "17:00",
                        "end_time" => "18:30",
                        "is_active" => true
                    ],
                    [
                        "id" => 16,
                        "plan_id" => 4,
                        "plan_name" => "اشتراك كيك بوكسينغ شهري",
                        "day_of_week" => 2,
                        "day_name" => "Tuesday",
                        "day_name_ar" => "الثلاثاء",
                        "start_time" => "17:00",
                        "end_time" => "18:30",
                        "is_active" => true
                    ]
                ]
            ]
        ]
    ]
)]
class CoachResource extends JsonResource
{
    public function toArray($request)
    {
        $contract = $this->activeContract;
        $detail = $this->coachDetail;
        $qrCode = $this->person_id 
            ? app(\Modules\Authentication\Services\PersonQrCodeService::class)->getSingleCodeForPerson($this->person_id) 
            : null;

        return [
            'id'              => $this->id,
            'person_id'       => $this->person_id,
            'qr_code'         => $qrCode,
            'branch_ids'      => $this->branches->pluck('id'),
            'role'            => $this->role,
            'employment_type' => $contract ? $contract->employment_type : null,
            'base_salary'     => $contract ? $contract->base_salary : 0,
            'start_date'      => $this->start_date?->toDateString() ?? $contract?->start_date?->toDateString() ?? $this->created_at?->toDateString(),
            'end_date'        => $this->end_date?->toDateString() ?? $contract?->end_date?->toDateString(),
            'start_time'      => $this->start_time,
            'end_time'        => $this->end_time,
            'work_status'     => $this->work_status,
            'reason'          => $this->reason,
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
                'private_commission_rate' => $contract ? $contract->private_commission_rate : 0,
                'created_at' => $detail->created_at,
                'updated_at' => $detail->updated_at,
            ] : null,
            'activities'     => CoachActivityResource::collection(
                $this->activities ? $this->activities->each(fn($act) => $act->setRelation('coach', $this->resource)) : collect()
            ),
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
