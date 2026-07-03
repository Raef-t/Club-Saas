<?php

namespace Modules\AttendanceManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates requests to update who currently holds a locker key.
 *
 * holder_type:
 *   - 'member' → a registered member holds the key (holder_id required)
 *   - 'staff'  → a staff/coach holds the key (holder_id required)
 *   - 'guest'  → an unregistered guest holds the key (holder_name required, holder_id ignored)
 */
class UpdateLockerHolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'holder_type' => ['required', 'string', 'in:member,staff,guest'],
            // holder_id is required for member and staff, ignored for guest
            'holder_id'   => ['required_unless:holder_type,guest', 'nullable', 'integer'],
            'holder_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'holder_type.required'        => 'يجب تحديد نوع الحامل (member، staff، أو guest).',
            'holder_type.in'              => 'نوع الحامل يجب أن يكون member أو staff أو guest.',
            'holder_id.required_unless'   => 'معرّف الشخص مطلوب عند اختيار member أو staff.',
            'holder_name.required'        => 'يجب إدخال اسم الحامل.',
        ];
    }
}
