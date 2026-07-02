<?php

namespace Modules\AttendanceManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request to release (return or transfer) a locker key.
 *
 * release_type:
 *   - "return"   → Player hands the key back. Locker becomes available.
 *   - "transfer" → Player hands the key to a friend. The friend's name is logged.
 *                  Locker remains 'rented' conceptually but unlinked from this attendance.
 */
class ReleaseLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'release_type'     => ['required', 'string', 'in:return,transfer'],
            // Required only when the player transfers the key to a friend
            'transfer_to_name' => ['required_if:release_type,transfer', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'release_type.required'          => 'يجب تحديد نوع تسليم المفتاح (return أو transfer).',
            'release_type.in'                => 'نوع التسليم يجب أن يكون إما return أو transfer.',
            'transfer_to_name.required_if'   => 'يجب إدخال اسم الصديق عند نقل المفتاح إليه.',
        ];
    }
}
