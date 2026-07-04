<?php

namespace Modules\NotificationManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                       => ['required', 'string', 'max:255', 'min:3'],
            'body'                        => ['required', 'string', 'min:5', 'max:2000'],
            'sender_id'                   => ['nullable', 'integer', 'exists:authentication_users,id'],
            'sender_type'                 => ['nullable', 'string', 'in:admin,system,user'],

            // نوع الاستهداف: all | branch | custom
            'target_snapshot.type'        => ['required', 'string', 'in:all,branch,custom'],
            'target_snapshot.user_ids'    => ['required_if:target_snapshot.type,custom', 'array'],
            'target_snapshot.user_ids.*'  => ['integer', 'exists:authentication_users,id', 'distinct'],
            'target_snapshot.branch_id'   => ['required_if:target_snapshot.type,branch', 'nullable', 'integer', 'exists:branches,id'],

            // المرفقات (اختيارية)
            'attachments'    => ['nullable', 'array', 'max:5'],
            'attachments.*'  => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,mp4,mp3'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'                      => 'حقل العنوان مطلوب.',
            'title.min'                           => 'العنوان يجب أن يحتوي على الأقل :min أحرف.',
            'title.max'                           => 'العنوان يجب ألا يتجاوز :max حرفًا.',
            'body.required'                       => 'حقل المحتوى مطلوب.',
            'body.min'                            => 'المحتوى يجب أن يحتوي على الأقل :min أحرف.',
            'body.max'                            => 'المحتوى يجب ألا يتجاوز :max حرفًا.',
            'sender_id.exists'                    => 'المرسل المحدد غير موجود.',
            'sender_type.in'                      => 'نوع المرسل يجب أن يكون (admin, system, user).',
            'target_snapshot.type.required'       => 'نوع المستهدفين مطلوب.',
            'target_snapshot.type.in'             => 'نوع المستهدفين يجب أن يكون (all, branch, custom).',
            'target_snapshot.user_ids.required_if' => 'قائمة المستخدمين مطلوبة عند اختيار نوع "مخصص".',
            'target_snapshot.user_ids.*.exists'   => 'بعض معرفات المستخدمين غير موجودة.',
            'target_snapshot.branch_id.required_if' => 'الفرع مطلوب عند اختيار نوع "فرع".',
            'target_snapshot.branch_id.exists'    => 'الفرع المحدد غير موجود.',
            'attachments.max'                     => 'يمكنك رفع حد أقصى :max مرفقات.',
            'attachments.*.max'                   => 'كل مرفق يجب ألا يتجاوز 10 ميغابايت.',
            'attachments.*.mimes'                 => 'نوع الملف غير مدعوم.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'                    => 'العنوان',
            'body'                     => 'المحتوى',
            'sender_id'                => 'المرسل',
            'sender_type'              => 'نوع المرسل',
            'target_snapshot.type'     => 'نوع المستهدفين',
            'target_snapshot.user_ids' => 'قائمة المستخدمين',
            'target_snapshot.branch_id' => 'الفرع',
            'attachments'              => 'المرفقات',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            if (isset($data['target_snapshot']['type']) && $data['target_snapshot']['type'] === 'custom') {
                if (empty($data['target_snapshot']['user_ids'])) {
                    $validator->errors()->add(
                        'target_snapshot.user_ids',
                        'يجب تحديد مستخدم واحد على الأقل عند اختيار نوع "مخصص".'
                    );
                }
            }

            if (isset($data['target_snapshot']['type']) && $data['target_snapshot']['type'] === 'branch') {
                if (empty($data['target_snapshot']['branch_id'])) {
                    $validator->errors()->add(
                        'target_snapshot.branch_id',
                        'يجب تحديد فرع عند اختيار نوع "فرع".'
                    );
                }
            }
        });
    }
}
