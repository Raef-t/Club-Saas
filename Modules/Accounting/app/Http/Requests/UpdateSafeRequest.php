<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSafeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                => 'sometimes|string|max:100',
            'account_id'          => 'sometimes|integer|exists:acc_accounts,id',
            'currency'            => 'sometimes|in:USD,SYP',
            'responsible_user_id' => 'nullable|integer',
            'is_active'           => 'nullable|boolean',
            'notes'               => 'nullable|string',
            'branch_id'           => 'nullable|integer|exists:branches,id',
        ];
    }
}
