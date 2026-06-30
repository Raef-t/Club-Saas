<?php

namespace Modules\NotificationManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'nullable|array',
            'content' => 'nullable|array',
            'channel' => 'nullable|in:sms,email,whatsapp,push',
            'is_active' => 'nullable|boolean',
        ];
    }
}
