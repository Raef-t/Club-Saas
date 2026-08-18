<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'branch_id' => $isUpdate ? 'nullable|exists:branches,id' : 'required|exists:branches,id',
            'name' => ($isUpdate ? 'sometimes|' : '') . 'required|string|max:150',
            'session_count' => 'nullable|integer|min:1',
            'sessions_per_week' => 'nullable|integer|min:1',
            'base_price' => ($isUpdate ? 'sometimes|' : '') . 'required|numeric|min:0',
            'max_subscribers' => 'nullable|integer|min:0',
            'is_unlimited_subscribers' => 'nullable|boolean',
            'gender_restriction' => 'nullable|in:male,female,mixed',
            'status' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['active', 'inactive', 'completed'])],
            'activities' => 'nullable|array',
            'activities.*.activity_id' => 'required_with:activities|exists:activities,id',
            'activities.*.coach_id' => 'nullable|exists:staff,id',
            'session_templates' => 'nullable|array',
            'session_templates.*.facility_id' => 'nullable|exists:facilities,id',
            'session_templates.*.day_of_week' => 'required_with:session_templates|integer|between:0,6',
            'session_templates.*.start_time' => 'required_with:session_templates|date_format:H:i',
            'session_templates.*.end_time' => 'required_with:session_templates|date_format:H:i|after:session_templates.*.start_time',
        ];
    }
}
