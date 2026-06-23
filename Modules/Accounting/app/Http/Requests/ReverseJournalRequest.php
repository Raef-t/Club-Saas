<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReverseJournalRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'سبب عكس السند مطلوب',
            'reason.max'      => 'سبب العكس لا يمكن أن يتجاوز 500 حرف',
        ];
    }
}
