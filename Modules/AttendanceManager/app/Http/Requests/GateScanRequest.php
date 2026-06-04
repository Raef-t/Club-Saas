<?php

namespace Modules\AttendanceManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GateScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_token' => 'required|string',
            'scan_timestamp' => 'sometimes|integer',
        ];
    }
}
