<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewPlanSuspensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'suspend_start_date' => ['required', 'date'],
            'suspend_end_date'   => ['required', 'date', 'after_or_equal:suspend_start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'suspend_start_date.required' => __('تاريخ بداية الإيقاف مطلوب.'),
            'suspend_start_date.date' => __('تاريخ بداية الإيقاف غير صالح.'),
            'suspend_end_date.required' => __('تاريخ نهاية الإيقاف مطلوب.'),
            'suspend_end_date.date' => __('تاريخ نهاية الإيقاف غير صالح.'),
            'suspend_end_date.after_or_equal' => __('تاريخ نهاية الإيقاف يجب أن يكون بعد أو مساوياً لتاريخ البداية.'),
        ];
    }
}
