<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handle authorization via policies if needed
    }

    protected function prepareForValidation()
    {
        if ($this->has('branch_ids') && !is_array($this->branch_ids)) {
            $this->merge([
                'branch_ids' => is_string($this->branch_ids) && str_contains($this->branch_ids, ',')
                    ? explode(',', $this->branch_ids)
                    : [$this->branch_ids]
            ]);
        }

        // Filter empty values from arrays sent via multipart/form-data
        $arrayFields = ['branch_ids', 'work_types', 'activity_ids', 'shifts'];
        foreach ($arrayFields as $field) {
            if ($this->has($field) && is_array($this->input($field))) {
                $filtered = array_filter($this->input($field), fn($value) => !is_null($value) && $value !== '');
                // array_filter preserves keys, re-index it using array_values
                $this->merge([$field => array_values($filtered)]);
            }
        }

        $workTypes = $this->input('work_types', []);
        if (is_array($workTypes)) {
            if (in_array('equipment', $workTypes) && !in_array('activities', $workTypes)) {
                $this->merge([
                    'default_commission_rate' => 0,
                    'commission_rate' => 0,
                ]);
            }
            if (in_array('activities', $workTypes) && !in_array('equipment', $workTypes)) {
                $this->merge([
                    'base_salary' => 0,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            // Person Fields
            'first_name'              => ['required', 'string', 'max:255'],
            'last_name'               => ['required', 'string', 'max:255'],
            'gender'                  => ['nullable', 'string', 'in:male,female'],
            'age'                     => ['nullable', 'integer', 'min:18', 'max:100'],
            'dob'                     => ['nullable', 'date'],
            'phone_number'            => ['nullable', 'string', 'max:20'],
            'country_code'            => ['nullable', 'string', 'max:10'],
            'address'                 => ['nullable', 'string', 'max:500'],
            'photo'                   => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],

            // Staff & Coach Details
            'branch_ids'              => ['required', 'array'],
            'branch_ids.*'            => ['exists:branches,id'],
            'employment_type'         => ['nullable', 'string', 'in:fixed_salary,commission_based,hybrid'],
            'base_salary'             => ['nullable', 'numeric', 'min:0'],
            'specialization'          => ['nullable', 'string', 'max:255'],
            'bio'                     => ['nullable', 'string'],
            'experience_years'        => ['nullable', 'integer', 'min:0'],
            'payment_type'            => ['nullable', 'string'],
            'commission_type'         => ['nullable', 'string'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0'],
            'gym_type'                => ['nullable', 'string', 'in:male,female,mixed'],
            'start_date'              => ['nullable', 'date'],
            'end_date'                => ['nullable', 'date', 'after_or_equal:start_date'],
            'work_types'              => ['nullable', 'array'],
            'work_types.*'            => ['string', 'in:equipment,activities'],
            'work_status'             => ['nullable', 'string', 'in:active,suspended,on_leave'],
            'activity_ids'            => ['nullable', 'array'],
            'activity_ids.*'          => ['exists:activities,id'],
            'shifts'                  => ['nullable', 'array'],
            'shifts.*'                => ['exists:branch_shifts,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('shifts') && !empty($this->shifts)) {
                if (!$this->has('activity_ids') || empty($this->activity_ids)) {
                    $validator->errors()->add('shifts', 'لا يمكن تحديد شفتات بدون تحديد أنشطة للمدرب.');
                } else {
                    $activities = \Modules\Sports\Models\Activity::whereIn('id', $this->activity_ids)
                        ->with('activityType')
                        ->get();

                    $hasValidType = false;
                    $validNames = ['تدريب عام', 'group training', 'public training', 'تدريب جماعي'];

                    foreach ($activities as $activity) {
                        if ($activity->activityType) {
                            $nameData = $activity->activityType->name;
                            if (is_string($nameData)) {
                                $nameData = json_decode($nameData, true) ?? $nameData;
                            }
                            
                            if (is_array($nameData)) {
                                foreach ($nameData as $value) {
                                    if (in_array(strtolower(trim($value)), $validNames)) {
                                        $hasValidType = true;
                                        break 2;
                                    }
                                }
                            } elseif (is_string($nameData)) {
                                 if (in_array(strtolower(trim($nameData)), $validNames)) {
                                     $hasValidType = true;
                                     break;
                                 }
                            }
                        }
                    }

                    if (!$hasValidType) {
                        $validator->errors()->add('shifts', 'لا يمكن تعيين شفتات للمدرب إلا إذا كان النشاط من نوع تدريب عام.');
                    }
                }
            }
        });
    }
}
