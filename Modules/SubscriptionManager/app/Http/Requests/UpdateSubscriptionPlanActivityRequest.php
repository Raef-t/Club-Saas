<?php
namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlanActivityRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules(): array
    {
        return [
            'activity_id' => 'nullable|integer|exists:activities,id',
            'sessions_count' => 'nullable|integer|min:1',
            'is_unlimited' => 'nullable|boolean',
        ];
    }
}
