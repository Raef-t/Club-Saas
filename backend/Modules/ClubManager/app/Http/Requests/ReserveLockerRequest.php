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
            'holder_type' => 'required|string|in:member,staff,coach',
            'holder_id' => 'nullable|integer',
            'holder_name' => 'nullable|string|max:255',
            'price' => 'required_if:reservation_type,rental|numeric|min:0',
            'start_date' => [
                'required_if:reservation_type,rental',
                'prohibited_if:holder_type,coach',
                'nullable',
                'date',
            ],
            'end_date' => [
                'required_if:reservation_type,rental',
                'prohibited_if:holder_type,coach',
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }

    public function messages()
    {
        return [
            'start_date.prohibited_if' => __('Start date is prohibited when assigned to a coach.'),
            'end_date.prohibited_if' => __('End date is prohibited when assigned to a coach.'),
        ];
    }
}
