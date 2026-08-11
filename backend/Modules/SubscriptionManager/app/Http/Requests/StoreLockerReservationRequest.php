<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLockerReservationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'locker_id' => 'required|integer',
            'member_id' => 'nullable|integer|exists:members,id|required_without:staff_id',
            'staff_id' => 'nullable|integer|exists:staff,id|required_without:member_id',
            'start_date' => 'required|date',
            // If member_id is provided, end_date will be calculated as 1 month in controller.
            // If staff_id is provided, end_date will be null.
        ];
    }
}
