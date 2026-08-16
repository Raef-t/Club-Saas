<?php

namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'mobile_country_code' => 'nullable|string|max:5',
            'mobile' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'age' => 'nullable|integer|min:0',
            'dob' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            
            'additional_contacts' => 'nullable|array',
            'additional_contacts.*.name' => 'required_with:additional_contacts|string|max:100',
            'additional_contacts.*.country_code' => 'nullable|string|max:5',
            'additional_contacts.*.phone_number' => 'required_with:additional_contacts|string|max:20',
            'additional_contacts.*.relation' => 'nullable|string|max:50',
            
            'branch_id' => 'nullable|exists:branches,id',
        ];
    }
}
