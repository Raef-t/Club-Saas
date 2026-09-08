<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPlanActivityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'plan_id' => 'required|integer|exists:subscription_plans,id',
            'activity_id' => [
                'required',
                'integer',
                Rule::exists('activities', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
            'coach_id' => [
                'nullable',
                'integer',
                Rule::exists('staff', 'id')->where(function ($query) {
                    $query->where('role', 'coach')
                          ->where('is_active', true)
                          ->where('work_status', 'active')
                          ->whereNull('deleted_at');
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'activity_id.exists' => __('النشاط الرياضي المحدد غير موجود أو غير نشط.'),
            'coach_id.exists' => __('المدرب المحدد غير موجود أو غير نشط.'),
        ];
    }
}
