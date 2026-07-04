<?php

namespace Modules\NotificationManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminNotificationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'         => ['nullable', 'integer', 'exists:authentication_users,id'],
            'from'            => ['nullable', 'date', 'before_or_equal:to'],
            'to'              => ['nullable', 'date', 'after_or_equal:from'],
            'read'            => ['nullable', 'boolean'],
            'sender_type'     => ['nullable', 'in:admin,system,user'],
            'has_attachments' => ['nullable', 'boolean'],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'            => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists'           => 'المستخدم المحدد غير موجود',
            'from.date'                => 'تاريخ البداية غير صالح',
            'from.before_or_equal'     => 'تاريخ البداية يجب أن يكون قبل أو يساوي تاريخ النهاية',
            'to.date'                  => 'تاريخ النهاية غير صالح',
            'to.after_or_equal'        => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية',
            'read.boolean'             => 'قيمة حالة القراءة يجب أن تكون true أو false',
            'sender_type.in'           => 'نوع المرسل غير مدعوم',
            'has_attachments.boolean'  => 'قيمة وجود المرفقات يجب أن تكون true أو false',
            'per_page.integer'         => 'عدد العناصر في الصفحة يجب أن يكون رقمًا',
            'per_page.max'             => 'الحد الأقصى للعناصر في الصفحة هو 100',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('has_attachments')) {
            $this->merge(['has_attachments' => $this->boolean('has_attachments')]);
        }
        if ($this->has('read')) {
            $this->merge(['read' => $this->boolean('read')]);
        }
    }
}
