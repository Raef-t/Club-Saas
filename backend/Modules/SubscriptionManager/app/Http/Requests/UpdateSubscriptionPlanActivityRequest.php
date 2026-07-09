<?php
namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlanActivityRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules(): array
    {
        return [
            'staff_activity_id' => 'nullable|integer|exists:staff_activities,id',
        ];
    }
}
