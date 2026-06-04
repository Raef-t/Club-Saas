<?php

namespace Modules\AttendanceManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QRCheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_token' => 'required|string',
            'club_id' => 'required|integer',
            'branch_id' => 'required|integer',
        ];
    }
}
