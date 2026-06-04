<?php

namespace Modules\NotificationManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestSendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => 'required|exists:people,id',
            'data' => 'nullable|array',
        ];
    }
}
