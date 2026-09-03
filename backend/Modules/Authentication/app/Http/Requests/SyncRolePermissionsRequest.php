<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Permission;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions'   => 'required|array',
            'permissions.*' => 'string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $permissions = $this->input('permissions');
            if (is_array($permissions) && !empty($permissions)) {
                $existing = Permission::where('guard_name', 'sanctum')
                    ->whereIn('name', $permissions)
                    ->pluck('name')
                    ->toArray();

                $invalid = array_values(array_diff($permissions, $existing));

                if (!empty($invalid)) {
                    $invalidList = implode(', ', $invalid);
                    $validator->errors()->add(
                        'permissions',
                        "توجد بعض الصلاحيات المدخلة غير صحيحة أو غير موجودة في النظام: [{$invalidList}]"
                    );

                    foreach ($invalid as $inv) {
                        $validator->errors()->add('invalid_permissions', $inv);
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'قائمة الصلاحيات مطلوبة.',
            'permissions.array'    => 'يجب أن تكون الصلاحيات على شكل قائمة (array).',
            'permissions.*.string' => 'يجب أن تكون كل صلاحية نصاً صالحاً.',
        ];
    }
}
