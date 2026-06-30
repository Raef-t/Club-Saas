<?php
namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionFreezeRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [];
    }
}
