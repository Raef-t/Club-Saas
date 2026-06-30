<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCounterpartyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => 'sometimes|string|max:150',
            'type'           => 'sometimes|in:customer,vendor,employee,other',
            'ar_account_id'  => 'nullable|integer|exists:acc_accounts,id',
            'country_code'   => 'nullable|string|max:5',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:150',
            'reference_type' => 'nullable|string|max:100',
            'reference_id'   => 'nullable|integer',
            'notes'          => 'nullable|string',
        ];
    }
}
