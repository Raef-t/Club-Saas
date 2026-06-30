<?php
namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerSubscriptionItemRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [];
    }
}
