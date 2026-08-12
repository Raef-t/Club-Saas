<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuspendPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'suspend_start_date' => ['required', 'date', 'after_or_equal:today'],
            'suspend_end_date'   => ['required', 'date', 'after_or_equal:suspend_start_date'],
            'reason'             => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'suspend_start_date.required' => __('تاريخ بداية الإيقاف مطلوب.'),
            'suspend_start_date.date' => __('تاريخ بداية الإيقاف غير صالح.'),
            'suspend_start_date.after_or_equal' => __('تاريخ بداية الإيقاف يجب أن يكون اليوم أو تاريخاً مستقبلياً.'),
            'suspend_end_date.required' => __('تاريخ نهاية الإيقاف مطلوب.'),
            'suspend_end_date.date' => __('تاريخ نهاية الإيقاف غير صالح.'),
            'suspend_end_date.after_or_equal' => __('تاريخ نهاية الإيقاف يجب أن يكون بعد أو مساوياً لتاريخ البداية.'),
        ];
    }
}
