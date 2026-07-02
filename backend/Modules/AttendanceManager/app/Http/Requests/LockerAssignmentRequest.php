<?php

namespace Modules\AttendanceManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request to assign a locker key to a checked-in attendance.
 */
class LockerAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The locker key to assign to this attendance
            'locker_id' => ['required', 'integer', 'exists:lockers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'locker_id.required' => 'يجب تحديد رقم المفتاح.',
            'locker_id.exists'   => 'المفتاح المحدد غير موجود في النظام.',
        ];
    }
}
