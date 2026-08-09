<?php

namespace Modules\NotificationManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationToUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // 1. تحويل user_ids إلى مصفوفة أرقام صحيحة مهما كان شكل المدخل (نص، JSON، أو قائمة)
        if ($this->has('user_ids')) {
            $userIds = $this->input('user_ids');

            if (is_string($userIds)) {
                $decoded = json_decode($userIds, true);
                if (is_array($decoded)) {
                    $userIds = $decoded;
                } else {
                    $userIds = array_map('trim', explode(',', $userIds));
                }
            }

            if (is_array($userIds)) {
                $this->merge([
                    'user_ids' => array_map('intval', array_filter($userIds, 'is_numeric')),
                ]);
            }
        }

        // 2. معالجة المرفقات (سواء تم رفع ملف واحد كـ attachments=@file أو كـ مصفوفة attachments[])
        if ($this->hasFile('attachments')) {
            $file = $this->file('attachments');
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $this->files->set('attachments', [$file]);
            }
        } elseif ($this->has('attachments')) {
            // إزالة حقل المرفقات إذا كان نص عادي مثل "string"
            $attachments = $this->input('attachments');
            if (is_string($attachments) || empty($this->file('attachments'))) {
                $this->offsetUnset('attachments');
            }
        }
    }

    public function rules(): array
    {
        return [
            'user_ids'       => ['required', 'array', 'min:1'],
            'user_ids.*'     => ['required', 'integer', 'distinct', 'exists:authentication_users,id'],
            'title'          => ['required', 'string', 'min:3', 'max:255'],
            'body'           => ['required', 'string', 'min:5', 'max:2000'],
            'sender_type'    => ['nullable', 'string', 'in:admin,system,user'],
            'attachments' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (!is_array($value) && !($value instanceof \Illuminate\Http\UploadedFile)) {
                        $fail('حقل المرفقات يجب أن يكون ملفاً أو مصفوفة ملفات.');
                    }
                    if (is_array($value) && count($value) > 5) {
                        $fail('الحد الأقصى للمرفقات هو 5 ملفات.');
                    }
                },
            ],
            'attachments.*'  => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,mp4,mp3'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required'   => 'حقل قائمة المستخدمين (user_ids) مطلوب.',
            'user_ids.min'        => 'يجب تحديد مستخدم واحد على الأقل.',
            'user_ids.*.exists'   => 'أحد المستخدمين المحددين غير موجود في النظام.',
            'title.required'      => 'حقل العنوان مطلوب.',
            'body.required'       => 'حقل المحتوى مطلوب.',
            'attachments.max'     => 'الحد الأقصى للمرفقات هو 5 ملفات.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_ids'    => 'قائمة المستخدمين',
            'title'       => 'العنوان',
            'body'        => 'المحتوى',
            'sender_type' => 'نوع المرسل',
            'attachments' => 'المرفقات',
        ];
    }
}
