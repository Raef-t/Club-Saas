<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
                $this->merge([$field => array_values($filtered)]);
            }
        }

        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // Person Fields
            'first_name'              => ['nullable', 'string', 'max:255'],
            'last_name'               => ['nullable', 'string', 'max:255'],
            'gender'                  => ['nullable', 'string', 'in:male,female'],
            'age'                     => ['nullable', 'integer', 'min:18', 'max:100'],
            'dob'                     => ['nullable', 'date'],
            'phone_number'            => ['nullable', 'string', 'max:20'],
            'national_id'             => ['nullable', 'string', 'max:20'],
            'address'                 => ['nullable', 'string', 'max:500'],

            // Basic Info
            'base_salary'             => ['nullable', 'numeric', 'min:0'],
            'employment_type'         => ['nullable', 'string', 'in:fixed_salary,commission_based,hybrid'],
            'specialization'          => ['nullable', 'string', 'max:255'],
            'start_date'              => ['nullable', 'date'],
            'end_date'                => ['nullable', 'date', 'after_or_equal:start_date'],
            'work_types'              => ['nullable', 'array'],
            'work_types.*'            => ['string', 'in:equipment,activities'],
            'work_status'             => ['nullable', 'string'],
            'is_active'               => ['nullable', 'boolean'],
            'branch_ids'              => ['nullable', 'array'],
            'branch_ids.*'            => ['exists:branches,id'],

            // Details Info
            'bio'                     => ['nullable', 'string'],
            'experience_years'        => ['nullable', 'integer', 'min:0'],
            'gym_type'                => ['nullable', 'string', 'in:male,female,mixed'],
            'payment_type'            => ['nullable', 'string'],
            'commission_type'         => ['nullable', 'string'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0'],

            // Activities & Shifts
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
                $coachId = $this->route('id') ?? $this->route('coach');

                if ($this->has('activity_ids')) {
                    $activityIds = $this->activity_ids;
                } else {
                    $coach = \Modules\StaffManager\Models\Staff::find($coachId);
                    $activityIds = $coach ? $coach->activities()->pluck('activities.id')->toArray() : [];
                }

                if (empty($activityIds)) {
                    $validator->errors()->add('shifts', 'لا يمكن تحديد شفتات بدون تحديد أنشطة للمدرب.');
                    return;
                }

                $activities = \Modules\Sports\Models\Activity::whereIn('id', $activityIds)
                    ->with('activityType')
                    ->get();

                $hasValidType = false;
                $validNames = ['تدريب عام', 'تدريب خاص', 'group training', 'private training', 'public training', 'تدريب جماعي'];

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
                    $validator->errors()->add('shifts', 'لا يمكن تعيين شفتات للمدرب إلا إذا كان النشاط من نوع تدريب عام أو تدريب خاص.');
                }
            }
        });
    }
}
