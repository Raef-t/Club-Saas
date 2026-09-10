<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $merge = [];
        if ($this->has('is_unlimited_subscribers') && filter_var($this->is_unlimited_subscribers, FILTER_VALIDATE_BOOLEAN)) {
            $merge['max_subscribers'] = 0;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'nullable|string|max:150',
            'session_count' => 'nullable|integer|min:1',
            'sessions_per_week' => 'nullable|integer|min:1',
            'base_price' => 'nullable|numeric|min:0',
            'coach_price' => 'nullable|numeric|min:0',
            'branch_price' => 'nullable|numeric|min:0',
            'max_subscribers' => 'nullable|integer|min:0',
            'is_unlimited_subscribers' => 'nullable|boolean',
            'gender_restriction' => 'nullable|in:male,female,mixed',
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'completed'])],
            'activities' => 'nullable|array',
            'activities.*.activity_id' => [
                'required_with:activities',
                Rule::exists('activities', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
            'activities.*.coach_id' => [
                'nullable',
                Rule::exists('staff', 'id')->where(function ($query) {
                    $query->where('role', 'coach')
                          ->where('is_active', true)
                          ->where('work_status', 'active')
                          ->whereNull('deleted_at');
                }),
            ],
            'session_templates' => 'nullable|array',
            'session_templates.*.facility_id' => 'nullable|exists:facilities,id',
            'session_templates.*.day_of_week' => 'required_with:session_templates|integer|between:0,6',
            'session_templates.*.start_time' => 'required_with:session_templates|date_format:H:i',
            'session_templates.*.end_time' => 'required_with:session_templates|date_format:H:i|after:session_templates.*.start_time',
        ];
    }

    public function messages(): array
    {
        return [
            'activities.*.activity_id.exists' => __('النشاط الرياضي المحدد غير موجود أو غير نشط.'),
            'activities.*.coach_id.exists' => __('المدرب المحدد غير موجود أو غير نشط.'),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $planId = $this->route('subscription_plan') ?? $this->route('id');
            $plan = is_numeric($planId) ? \Modules\SubscriptionManager\Models\SubscriptionPlan::with(['planActivities.staffActivity'])->find($planId) : null;

            // 1. Group Activity Restriction
            if ($this->has('activities') && is_array($this->activities)) {
                $activityIds = collect($this->activities)
                    ->pluck('activity_id')
                    ->filter(fn($id) => is_numeric($id))
                    ->unique()
                    ->toArray();

                if (count($activityIds) > 1) {
                    $hasGroupActivity = \Modules\Sports\Models\Activity::hasAnyGroupActivity($activityIds);
                    if ($hasGroupActivity) {
                        $validator->errors()->add(
                            'activities',
                            __('لا يمكن إضافة أكثر من نشاط واحد لخطة الاشتراك عندما يكون نوع النشاط حصة جماعية.')
                        );
                    }
                }
            }

            // 2. Session Templates Timing & Conflict Validation
            if ($this->has('session_templates') && is_array($this->session_templates) && !empty($this->session_templates)) {
                $conflictService = app(\Modules\Sports\Services\SessionConflictService::class);
                $branchId = $this->filled('branch_id') ? (int) $this->input('branch_id') : ($plan?->branch_id);

                $activityIds = [];
                $coachIds = [];
                if ($this->has('activities') && is_array($this->activities)) {
                    $activityIds = collect($this->activities)->pluck('activity_id')->filter(fn($id) => is_numeric($id))->unique()->toArray();
                    $coachIds = collect($this->activities)->pluck('coach_id')->filter(fn($id) => is_numeric($id))->unique()->toArray();
                } elseif ($plan) {
                    $activityIds = $plan->planActivities->pluck('activity_id')->filter()->unique()->toArray();
                    $coachIds = $plan->planActivities->pluck('staffActivity.staff_id')->filter()->unique()->toArray();
                }

                $isGroup = \Modules\Sports\Models\Activity::hasAnySessionBasedActivity($activityIds);
                if (!$isGroup && $plan) {
                    $isGroup = $plan->isGroupSessionPlan();
                }
                if (!$branchId && !empty($activityIds)) {
                    $branchId = \Modules\Sports\Models\Activity::whereIn('id', $activityIds)->value('branch_id');
                }
                if (!$isGroup && $this->filled('name')) {
                    $planName = (string) $this->input('name');
                    foreach (['حصة جماعية', 'حصة_جماعية', 'حصة جماعيه', 'جماعي', 'جماعية', 'group', 'session'] as $kw) {
                        if (str_contains($planName, $kw)) {
                            $isGroup = true;
                            break;
                        }
                    }
                }

                $conflictError = $conflictService->validateTemplates(
                    $this->session_templates,
                    $branchId,
                    $plan?->id, // ignorePlanId
                    null,       // ignoreTemplateId
                    $coachIds,
                    $isGroup
                );

                if ($conflictError) {
                    $validator->errors()->add('session_templates', $conflictError);
                }
            }
        });
    }
}
