<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCoachCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'issuer'       => ['nullable', 'string', 'max:255'],
            'issue_date'   => ['nullable', 'date'],
            'expiry_date'  => ['nullable', 'date', 'after_or_equal:issue_date'],
            'file'         => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'document_url' => ['nullable', 'string', 'url'],
        ];
    }
}
