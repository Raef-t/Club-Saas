<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                => 'sometimes|string|max:100',
            'capital_account_id'  => 'sometimes|integer|exists:acc_accounts,id',
            'drawings_account_id' => 'nullable|integer|exists:acc_accounts,id',
            'profit_share_pct'    => 'sometimes|numeric|min:0|max:100',
            'joined_at'           => 'sometimes|date',
            'is_active'           => 'nullable|boolean',
            'notes'               => 'nullable|string',
        ];
    }
}
