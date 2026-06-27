<?php
namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPlanActivityRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'plan_id' => 'required|integer',
            'activity_id' => 'required|integer',
        ];
    }
}
