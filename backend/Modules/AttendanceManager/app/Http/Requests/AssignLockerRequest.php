<?php

namespace Modules\AttendanceManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request to assign a locker key directly to a member or staff
 * without requiring an attendance record.
 */
class AssignLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'holder_type' => ['required', 'string', 'in:member,staff'],
            'holder_id'   => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'holder_type.required' => 'يجب تحديد نوع الحامل (member أو staff).',
            'holder_type.in'       => 'نوع الحامل يجب أن يكون member أو staff.',
            'holder_id.required'   => 'يجب تحديد ID العضو أو الموظف.',
            'holder_id.integer'    => 'ID غير صالح.',
        ];
    }
}
