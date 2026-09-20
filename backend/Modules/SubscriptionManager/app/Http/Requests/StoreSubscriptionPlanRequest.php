<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $merge = [];
        
        $activityIds = [];
        if ($this->has('activities') && is_array($this->activities)) {
            foreach ($this->activities as $act) {
                if (isset($act['activity_id']) && is_numeric($act['activity_id'])) {
                    $activityIds[] = (int) $act['activity_id'];
                }
            }
        } elseif ($this->route('subscription_plan') || $this->route('id')) {
            $planId = $this->route('subscription_plan') ?? $this->route('id');
            if (is_numeric($planId)) {
                $plan = \Modules\SubscriptionManager\Models\SubscriptionPlan::find($planId);
                if ($plan) {
                    $activityIds = $plan->planActivities()->get()->pluck('activity_id')->filter()->toArray();
                }
            }
        }

        $isEquipmentPlan = !empty($activityIds) && \Modules\Sports\Models\Activity::hasAnyEquipmentActivity($activityIds);

        if ($isEquipmentPlan || ($this->has('is_unlimited_subscribers') && filter_var($this->is_unlimited_subscribers, FILTER_VALIDATE_BOOLEAN))) {
            $merge['max_subscribers'] = 0;
            $merge['is_unlimited_subscribers'] = true;
        }

        if ($this->filled('coach_price') || $this->filled('branch_price')) {
            $coachPrice = (float) $this->input('coach_price', 0);
            $branchPrice = (float) $this->input('branch_price', 0);
            if (!$this->filled('base_price') && !$this->filled('price')) {
                $merge['base_price'] = $coachPrice + $branchPrice;
            }
        }
        if ($this->filled('price') && !$this->filled('base_price')) {
            $merge['base_price'] = $this->input('price');
        }

        if ($this->filled('sessions_per_week') && !$this->filled('session_count')) {
            $merge['session_count'] = (int) $this->input('sessions_per_week') * 4;
        }

        $branchId = $this->input('branch_id');
        if ($branchId && is_numeric($branchId)) {
            $branch = \Modules\ClubManager\Models\Branch::find($branchId);
            if ($branch && in_array($branch->gender_restriction, ['male', 'female']) && !$this->filled('gender_restriction')) {
                $merge['gender_restriction'] = $branch->gender_restriction;
            }
        }

        if ($this->has('is_active') && !$this->has('status')) {
            $merge['status'] = filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN) ? 'active' : 'inactive';
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        $hasSplitPricing = $this->filled('coach_price') || $this->filled('branch_price');

        return [
            'branch_id' => $isUpdate ? 'nullable|exists:branches,id' : 'required|exists:branches,id',
            'name' => ($isUpdate ? 'sometimes|' : '') . 'required|string|max:150',
            'session_count' => 'nullable|integer|min:1',
            'sessions_per_week' => 'nullable|integer|min:1',
            'base_price' => ($isUpdate ? 'sometimes|' : '') . ($hasSplitPricing ? 'nullable|' : 'required|') . 'numeric|min:0',
            'coach_price' => 'nullable|numeric|min:0',
            'branch_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'max_subscribers' => 'nullable|integer|min:0',
            'is_unlimited_subscribers' => 'nullable|boolean',
            'gender_restriction' => 'nullable|in:male,female,mixed',
            'status' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['active', 'inactive', 'completed'])],
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
            // 1. Group Activity Restriction: A plan cannot have more than 1 activity if any activity is a group session (حصة جماعية)
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
                $branchId = $this->filled('branch_id') ? (int) $this->input('branch_id') : null;
                $activityIds = collect($this->input('activities', []))
                    ->pluck('activity_id')
                    ->filter(fn($id) => is_numeric($id))
                    ->unique()
                    ->toArray();
                $coachIds = collect($this->input('activities', []))
                    ->pluck('coach_id')
                    ->filter(fn($id) => is_numeric($id))
                    ->unique()
                    ->toArray();

                $isGroup = \Modules\Sports\Models\Activity::hasAnySessionBasedActivity($activityIds);
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

                $isPlanActive = ($this->input('status', 'active') !== 'inactive');

                $conflictError = $conflictService->validateTemplates(
                    $this->session_templates,
                    $branchId,
                    null, // ignorePlanId
                    null, // ignoreTemplateId
                    $coachIds,
                    $isGroup,
                    $isPlanActive
                );

                if ($conflictError) {
                    $validator->errors()->add('session_templates', $conflictError);
                }
            }

            // 3. Weekly sessions vs Total sessions validation
            if ($this->filled('sessions_per_week') && $this->filled('session_count')) {
                $expectedCount = (int) $this->input('sessions_per_week') * 4;
                if ((int) $this->input('session_count') !== $expectedCount) {
                    $validator->errors()->add(
                        'session_count',
                        __('عدد الجلسات الإجمالي يجب أن يساوي عدد الجلسات في الأسبوع مضروباً في 4 (أسابيع الشهر).')
                    );
                }
            }

            // 4. Session Templates count validation vs sessions_per_week
            if ($this->filled('sessions_per_week') && $this->has('session_templates') && is_array($this->session_templates) && !empty($this->session_templates)) {
                $templatesCount = count($this->session_templates);
                $expectedCount = (int) $this->input('sessions_per_week');
                if ($templatesCount !== $expectedCount) {
                    $validator->errors()->add(
                        'session_templates',
                        __('عدد أوقات جدول الفعالية (:count) يجب أن يتطابق مع عدد الجلسات في الأسبوع (:expected).', [
                            'count' => $templatesCount,
                            'expected' => $expectedCount,
                        ])
                    );
                }
            }

            // 5. Branch Gender Restriction
            $branchId = $this->filled('branch_id') ? (int) $this->input('branch_id') : null;
            if ($branchId) {
                $branch = \Modules\ClubManager\Models\Branch::find($branchId);
                if ($branch) {
                    $gender = $this->input('gender_restriction');
                    if ($branch->gender_restriction === 'female' && $gender && $gender !== 'female') {
                        $validator->errors()->add(
                            'gender_restriction',
                            __('لا يمكن تحديد هذا الجنس، لأن الفرع مخصص للإناث فقط.')
                        );
                    } elseif ($branch->gender_restriction === 'male' && $gender && $gender !== 'male') {
                        $validator->errors()->add(
                            'gender_restriction',
                            __('لا يمكن تحديد هذا الجنس، لأن الفرع مخصص للذكور فقط.')
                        );
                    }
                }
            }
        });
    }
}
