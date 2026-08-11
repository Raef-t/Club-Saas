<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferLockerReservationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'holder_type' => 'required|string|in:member,staff,coach',
            'holder_id' => 'nullable|integer',
            'holder_name' => 'nullable|string|max:255',
        ];
    }
}
