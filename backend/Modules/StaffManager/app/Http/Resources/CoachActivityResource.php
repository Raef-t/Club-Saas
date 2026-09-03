<?php

namespace Modules\StaffManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sports\Http\Resources\ActivityTypeResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CoachActivityScheduleItem",
    title: "Coach Activity Schedule Item",
    description: "موعد جلسة تدريبية أسبوعية للنشاط",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 15),
        new OA\Property(property: "plan_id", type: "integer", example: 4),
        new OA\Property(property: "plan_name", type: "string", example: "اشتراك كيك بوكسينغ شهري"),
        new OA\Property(property: "day_of_week", type: "integer", example: 0, description: "0=الأحد، 1=الإثنين، ...، 6=السبت"),
        new OA\Property(property: "day_name", type: "string", example: "Sunday"),
        new OA\Property(property: "day_name_ar", type: "string", example: "الأحد"),
        new OA\Property(property: "start_time", type: "string", format: "time", example: "17:00"),
        new OA\Property(property: "end_time", type: "string", format: "time", example: "18:30"),
        new OA\Property(property: "is_active", type: "boolean", example: true)
    ]
)]
#[OA\Schema(
    schema: "CoachActivityShiftItem",
    title: "Coach Activity Shift Item",
    description: "شفت عمل للكوتش في التدريب العام",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 10),
        new OA\Property(property: "branch_shift_id", type: "integer", example: 2),
        new OA\Property(property: "name", type: "string", example: "الشفت الصباحي"),
        new OA\Property(property: "start_time", type: "string", format: "time", example: "08:00"),
        new OA\Property(property: "end_time", type: "string", format: "time", example: "16:00")
    ]
)]
#[OA\Schema(
    schema: "CoachActivityResource",
    title: "Coach Activity Resource",
    description: "تفاصيل النشاط المنسوب للمدرب متضمناً شفتات أو جدول المواعيد",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 3),
        new OA\Property(property: "name", type: "string", example: "كيك بوكسينغ"),
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "activity_type", ref: "#/components/schemas/ActivityTypeResource"),
        new OA\Property(property: "is_unlimited_subscribers", type: "boolean", example: false),
        new OA\Property(property: "description", type: "string", nullable: true, example: null),
        new OA\Property(property: "is_private_equipment", type: "boolean", example: false),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-07-16T12:00:00Z"),
        new OA\Property(property: "schedule_type", type: "string", enum: ["shifts", "schedule", "none"], example: "schedule", description: "نوع الجدولة: shifts للتدريب العام، schedule للحصص، none للتدريب الخاص"),
        new OA\Property(property: "shifts", type: "array", items: new OA\Items(ref: "#/components/schemas/CoachActivityShiftItem")),
        new OA\Property(property: "schedules", type: "array", items: new OA\Items(ref: "#/components/schemas/CoachActivityScheduleItem"))
    ],
    example: [
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
        "created_at" => "2026-07-16T12:00:00Z",
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
            ]
        ]
    ]
)]
class CoachActivityResource extends JsonResource
{
    public function toArray($request): array
    {
        $coach = $this->relationLoaded('coach') ? $this->getRelation('coach') : null;

        $activityType = $this->activityType;
        $typeName = '';
        if ($activityType) {
            if (is_array($activityType->name)) {
                $typeName = implode(' ', $activityType->name);
            } elseif (is_string($activityType->name)) {
                $nameData = json_decode($activityType->name, true);
                $typeName = is_array($nameData) ? implode(' ', $nameData) : $activityType->name;
            }
        }
        $actName = is_array($this->name) ? implode(' ', $this->name) : (string) $this->name;
        $combinedText = mb_strtolower(trim($typeName . ' ' . $actName));

        $isGeneralTraining = ($activityType && $activityType->has_shifts)
            || str_contains($combinedText, 'تدريب عام')
            || str_contains($combinedText, 'general training')
            || str_contains($combinedText, 'تدريب جماعي')
            || str_contains($combinedText, 'group training')
            || str_contains($combinedText, 'أجهزة عام')
            || str_contains($combinedText, 'اجهزة عام');

        $isPrivateTraining = (bool) $this->is_private_equipment
            || str_contains($combinedText, 'تدريب خاص')
            || str_contains($combinedText, 'private training')
            || str_contains($combinedText, 'تدريب شخصي')
            || str_contains($combinedText, 'تدريب فردي')
            || str_contains($combinedText, 'أجهزة خاص')
            || str_contains($combinedText, 'اجهزة خاص')
            || (str_contains($combinedText, 'خاص') && !str_contains($combinedText, 'عام'));

        $scheduleType = 'schedule';
        $shifts = [];
        $schedules = [];

        if ($isGeneralTraining) {
            $scheduleType = 'shifts';
            if ($coach && $coach->relationLoaded('shifts')) {
                $coachShifts = $coach->shifts;
                if ($this->branch_id) {
                    $branchFiltered = $coachShifts->filter(function ($s) {
                        return $s->branchShift && $s->branchShift->branch_id == $this->branch_id;
                    });
                    if ($branchFiltered->isNotEmpty()) {
                        $coachShifts = $branchFiltered;
                    }
                }

                $shifts = $coachShifts->map(function ($s) {
                    $startTime = $s->branchShift?->start_time;
                    $endTime = $s->branchShift?->end_time;
                    return [
                        'id'              => $s->id,
                        'branch_shift_id' => $s->branch_shift_id,
                        'name'            => $s->branchShift?->name,
                        'start_time'      => is_string($startTime) ? substr($startTime, 0, 5) : $startTime?->format('H:i'),
                        'end_time'        => is_string($endTime) ? substr($endTime, 0, 5) : $endTime?->format('H:i'),
                    ];
                })->values()->all();
            }
        } elseif ($isPrivateTraining) {
            $scheduleType = 'none';
        } else {
            $scheduleType = 'schedule';
            if ($coach) {
                $daysMap = [
                    0 => 'Sunday',
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                ];
                $arabicDaysMap = [
                    0 => 'الأحد',
                    1 => 'الإثنين',
                    2 => 'الثلاثاء',
                    3 => 'الأربعاء',
                    4 => 'الخميس',
                    5 => 'الجمعة',
                    6 => 'السبت',
                ];

                $sessionTemplates = collect();

                if ($coach->relationLoaded('staffActivities')) {
                    $staffActivity = $coach->staffActivities->firstWhere('activity_id', $this->id);
                    if ($staffActivity && $staffActivity->relationLoaded('planActivities')) {
                        foreach ($staffActivity->planActivities as $pa) {
                            if ($pa->relationLoaded('sessionTemplate') && $pa->sessionTemplate && $pa->sessionTemplate->is_active) {
                                $sessionTemplates->push($pa->sessionTemplate);
                            }
                            if ($pa->relationLoaded('plan') && $pa->plan && $pa->plan->relationLoaded('sessionTemplates')) {
                                foreach ($pa->plan->sessionTemplates as $st) {
                                    if ($st->is_active) {
                                        $st->setRelation('subscriptionPlan', $pa->plan);
                                        $sessionTemplates->push($st);
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $templates = \Modules\Sports\Models\SportSessionTemplate::where('is_active', true)
                        ->where(function ($query) use ($coach) {
                            $query->whereHas('subscriptionPlan.planActivities.staffActivity', function ($q) use ($coach) {
                                $q->where('staff_id', $coach->id)
                                  ->where('activity_id', $this->id);
                            })
                            ->orWhereHas('subscriptionPlan.planActivities', function ($q) use ($coach) {
                                $q->whereNotNull('session_template_id')
                                  ->whereHas('staffActivity', function ($sq) use ($coach) {
                                      $sq->where('staff_id', $coach->id)
                                         ->where('activity_id', $this->id);
                                  });
                            });
                        })
                        ->with('subscriptionPlan')
                        ->get();

                    $sessionTemplates = $sessionTemplates->merge($templates);
                }

                $schedules = $sessionTemplates->unique('id')->sortBy(function ($t) {
                    $timeStr = is_string($t->start_time) ? $t->start_time : ($t->start_time?->format('H:i') ?? '00:00');
                    return sprintf('%02d-%s', $t->day_of_week, $timeStr);
                })->map(function ($template) use ($daysMap, $arabicDaysMap) {
                    $startTime = $template->start_time;
                    $endTime = $template->end_time;
                    $plan = $template->subscriptionPlan ?? $template->plan;

                    return [
                        'id'          => $template->id,
                        'plan_id'     => $template->plan_id,
                        'plan_name'   => $plan?->name,
                        'day_of_week' => (int) $template->day_of_week,
                        'day_name'    => $daysMap[$template->day_of_week] ?? '',
                        'day_name_ar' => $arabicDaysMap[$template->day_of_week] ?? '',
                        'start_time'  => is_string($startTime) ? substr($startTime, 0, 5) : $startTime?->format('H:i'),
                        'end_time'    => is_string($endTime) ? substr($endTime, 0, 5) : $endTime?->format('H:i'),
                        'is_active'   => (bool) $template->is_active,
                    ];
                })->values()->all();
            }
        }

        return [
            'id'                       => $this->id,
            'name'                     => $this->name,
            'branch_id'                => $this->branch_id,
            'activity_type'            => new ActivityTypeResource($this->activityType),
            'is_unlimited_subscribers' => (bool) ($this->activityType?->has_unlimited_subscribers ?? (method_exists($this->resource, 'hasUnlimitedSubscribers') ? $this->hasUnlimitedSubscribers() : false)),
            'description'              => $this->description,
            'is_private_equipment'     => (bool) $this->is_private_equipment,
            'is_active'                => (bool) $this->is_active,
            'created_at'               => $this->created_at?->toIso8601String(),

            'schedule_type'            => $scheduleType,
            'shifts'                   => $shifts,
            'schedules'                => $schedules,
        ];
    }
}
