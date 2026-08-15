<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'          => 'sometimes|nullable|integer|exists:authentication_users,id',
            'first_name'       => 'sometimes|nullable|string|max:100',
            'last_name'        => 'sometimes|nullable|string|max:100',
            'phone_number'     => 'sometimes|nullable|string|max:20',
            'dob'              => 'sometimes|nullable|date_format:Y-m-d|before_or_equal:today',
            'gender'           => 'sometimes|nullable|in:male,female',
            'address'          => 'sometimes|nullable|string|max:255',
            'how_did_you_hear' => 'sometimes|nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists'              => 'المستخدم المحدد غير موجود في النظام.',
            'first_name.max'              => 'الاسم الأول يجب ألا يتجاوز 100 حرف.',
            'last_name.max'               => 'الاسم الأخير يجب ألا يتجاوز 100 حرف.',
            'phone_number.max'            => 'رقم الهاتف يجب ألا يتجاوز 20 حرفاً.',
            'dob.date_format'             => 'تاريخ الميلاد يجب أن يكون بصيغة Y-m-d (مثال: 1995-08-20).',
            'dob.before_or_equal'         => 'تاريخ الميلاد يجب أن يكون تاريخاً سابقاً أو مساوياً لليوم.',
            'gender.in'                   => 'الجنس يجب أن يكون إما male أو female.',
            'address.max'                 => 'العنوان السكني يجب ألا يتجاوز 255 حرفاً.',
            'how_did_you_hear.max'        => 'حقل كيف سمعت بالنادي يجب ألا يتجاوز 100 حرف.',
        ];
    }
}
