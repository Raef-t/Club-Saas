<?php

namespace Modules\NotificationManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => 'required|string|unique:notification_templates,slug',
            'subject' => 'required|array',
            'content' => 'required|array',
            'channel' => 'required|in:sms,email,whatsapp,push',
        ];
    }
}
