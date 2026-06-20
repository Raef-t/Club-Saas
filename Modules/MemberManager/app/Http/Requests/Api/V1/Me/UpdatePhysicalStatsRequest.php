<?php

namespace Modules\MemberManager\Http\Requests\Api\V1\Me;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhysicalStatsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We assume authentication is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'height' => ['nullable', 'numeric', 'min:50', 'max:250'],
            'weight' => ['nullable', 'numeric', 'min:20', 'max:300'],
            'dob' => ['nullable', 'date', 'before:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'height.numeric' => __('يجب أن يكون الطول رقماً.'),
            'height.min' => __('الطول غير منطقي.'),
            'height.max' => __('الطول غير منطقي.'),
            'weight.numeric' => __('يجب أن يكون الوزن رقماً.'),
            'weight.min' => __('الوزن غير منطقي.'),
            'weight.max' => __('الوزن غير منطقي.'),
            'dob.date' => __('تاريخ الميلاد يجب أن يكون تاريخاً صحيحاً.'),
            'dob.before' => __('تاريخ الميلاد يجب أن يكون في الماضي.'),
        ];
    }
}
