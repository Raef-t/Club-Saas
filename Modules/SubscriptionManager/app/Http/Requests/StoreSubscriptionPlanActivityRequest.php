<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'activity_id' => 'required|integer|exists:activities,id',
            'sessions_count' => 'nullable|integer|min:1',
            'is_unlimited' => 'nullable|boolean',
        ];
    }
}
