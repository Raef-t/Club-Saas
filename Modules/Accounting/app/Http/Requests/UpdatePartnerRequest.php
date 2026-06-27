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
            'profit_share_pct'    => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100',
                function ($attribute, $value, $fail) {
                    $partnerId = $this->route('id');
                    $isActive = $this->input('is_active');
                    if ($isActive === null) {
                        $partner = \Modules\Accounting\Models\AccPartner::find($partnerId);
                        $isActive = $partner ? $partner->is_active : true;
                    }
                    if ($isActive) {
                        $currentSum = \Modules\Accounting\Models\AccPartner::where('is_active', true)
                            ->where('id', '!=', $partnerId)
                            ->sum('profit_share_pct');
                        if ($currentSum + $value > 100) {
                            $fail('مجموع نسب الأرباح للشركاء النشطين لا يمكن أن يتجاوز 100%. النسبة المتبقية المتاحة هي: ' . (100 - $currentSum) . '%');
                        }
                    }
                }
            ],
            'joined_at'           => 'sometimes|date',
            'is_active'           => [
                'nullable',
                'boolean',
                function ($attribute, $value, $fail) {
                    if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
                        $partnerId = $this->route('id');
                        $partner = \Modules\Accounting\Models\AccPartner::find($partnerId);
                        if ($partner) {
                            $share = $this->input('profit_share_pct', $partner->profit_share_pct);
                            $currentSum = \Modules\Accounting\Models\AccPartner::where('is_active', true)
                                ->where('id', '!=', $partnerId)
                                ->sum('profit_share_pct');
                            if ($currentSum + $share > 100) {
                                $fail('لا يمكن تفعيل الشريك لأن مجموع نسب الأرباح سيتجاوز 100%. النسبة المتبقية المتاحة هي: ' . (100 - $currentSum) . '%');
                            }
                        }
                    }
                }
            ],
            'notes'               => 'nullable|string',
        ];
    }
}
