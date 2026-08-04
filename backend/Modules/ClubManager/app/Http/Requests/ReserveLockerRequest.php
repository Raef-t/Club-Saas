<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReserveLockerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reservation_type' => 'required|string|in:rental,assign',
            'holder_type' => 'required|string|in:member,staff,guest',
            'holder_id' => 'nullable|integer',
            'holder_name' => 'nullable|string|max:255',
            'price' => 'required_if:reservation_type,rental|numeric|min:0',
            'start_date' => 'required_if:reservation_type,rental|date',
            'end_date' => 'required_if:reservation_type,rental|date|after_or_equal:start_date',
        ];
    }
}
