<?php

namespace Modules\AttendanceManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QRCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_token' => 'required|string',
            'branch_id' => 'required|integer',
        ];
    }
}
