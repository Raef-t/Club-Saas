<?php
namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlayerSubscriptionItemRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'player_subscription_id' => 'required|integer',
        ];
    }
}
