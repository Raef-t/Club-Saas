<?php

namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_private_equipments') && !$this->has('is_private_equipment')) {
            $this->merge([
                'is_private_equipment' => $this->input('is_private_equipments'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_session_based' => 'boolean',
            'has_unlimited_subscribers' => 'boolean',
            'has_shifts' => 'boolean',
            'is_daily_entry' => 'boolean',
            'is_private_equipment' => 'boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasShiftsInput = $this->has('has_shifts')
                ? filter_var($this->input('has_shifts'), FILTER_VALIDATE_BOOLEAN)
                : null;

            if ($hasShiftsInput === true) {
                $activityType = $this->route('activity_type');
                if (is_numeric($activityType)) {
                    $activityType = \Modules\Sports\Models\ActivityType::find($activityType);
                }

                $isPrivateEquipment = $this->has('is_private_equipment')
                    ? filter_var($this->input('is_private_equipment'), FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($activityType?->is_private_equipment ?? false);

                if ($isPrivateEquipment) {
                    $validator->errors()->add(
                        'has_shifts',
                        __('لا يمكن تفعيل نظام الورديات عندما تكون المعدات خاصة (is_private_equipment).')
                    );
                }
            }
        });
    }
}
