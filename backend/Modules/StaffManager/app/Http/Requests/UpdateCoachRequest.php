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
        if ($this->has('branch_id') && !$this->has('branch_ids')) {
            $this->merge([
                'branch_ids' => is_array($this->branch_id) ? $this->branch_id : [$this->branch_id]
            ]);
        }

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

        if ($this->input('photo') === 'null' || $this->input('photo') === '' || $this->input('photo') === 'undefined') {
            $this->merge(['photo' => null]);
        }

        if ($this->has('delete_photo')) {
            $this->merge([
                'delete_photo' => filter_var($this->delete_photo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }

        if ($this->has('work_types')) {
            $workTypes = $this->input('work_types', []);
        } else {
            $coachId = $this->route('id') ?? $this->route('coach');
            $coach = \Modules\StaffManager\Models\Staff::find($coachId);
            $workTypes = $coach?->coachDetail?->work_types ?? [];
        }

        if (is_array($workTypes)) {
            if (in_array('equipment', $workTypes) && !in_array('activities', $workTypes)) {
                $this->merge([
                    'default_commission_rate' => 0,
                    'commission_rate'         => 0,
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
            'reason'                  => ['required', 'string', 'max:500'],

            // Person Fields
            'first_name'              => ['nullable', 'string', 'max:255'],
            'last_name'               => ['nullable', 'string', 'max:255'],
            'gender'                  => ['nullable', 'string', 'in:male,female'],
            'age'                     => ['nullable', 'integer', 'min:18', 'max:100'],
            'dob'                     => ['nullable', 'date'],
            'phone_number'            => ['nullable', 'string', 'max:20'],
            'country_code'            => ['nullable', 'string', 'max:10'],
            'address'                 => ['nullable', 'string', 'max:500'],
            'photo'                   => ['nullable'],
            'delete_photo'            => ['nullable', 'boolean'],

            // Basic Info
            'base_salary'             => ['nullable', 'numeric', 'min:0'],
            'employment_type'         => ['nullable', 'string', 'in:fixed_salary,commission_based,hybrid'],
            'specialization'          => ['nullable', 'string', 'max:255'],
            'start_date'              => ['nullable', 'date'],
            'end_date'                => ['nullable', 'date', 'after_or_equal:start_date'],
            'work_types'              => ['nullable', 'array'],
            'work_types.*'            => ['string', 'in:equipment,activities'],
            'work_status'             => ['nullable', 'string', 'in:active,suspended,on_leave'],
            'branch_ids'              => ['nullable', 'array'],
            'branch_ids.*'            => ['exists:branches,id'],

            // Details Info
            'bio'                     => ['nullable', 'string'],
            'experience_years'        => ['nullable', 'integer', 'min:0'],
            'gym_type'                => ['nullable', 'string', 'in:male,female,mixed'],
            'payment_type'            => ['nullable', 'string'],
            'commission_type'         => ['nullable', 'string'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'private_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],

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
            $coachId = $this->route('id') ?? $this->route('coach');
            $coach = \Modules\StaffManager\Models\Staff::with(['person', 'branches'])->find($coachId);
            $gender = $this->has('gender') ? $this->input('gender') : $coach?->person?->gender;

            if ($gender) {
                $branchIds = $this->has('branch_ids')
                    ? $this->input('branch_ids', [])
                    : ($coach ? $coach->branches->pluck('id')->toArray() : []);

                if (!empty($branchIds) && is_array($branchIds)) {
                    $branches = \Modules\ClubManager\Models\Branch::whereIn('id', $branchIds)->get();
                    foreach ($branches as $branch) {
                        if ($branch->gender_restriction && $branch->gender_restriction !== 'mixed' && $branch->gender_restriction !== $gender) {
                            $branchName = $branch->name;
                            if (is_string($branchName)) {
                                $decoded = json_decode($branchName, true);
                                if (is_array($decoded)) {
                                    $branchName = $decoded['ar'] ?? ($decoded['en'] ?? reset($decoded));
                                }
                            } elseif (is_array($branchName)) {
                                $branchName = $branchName['ar'] ?? ($branchName['en'] ?? reset($branchName));
                            }

                            $msg = $branch->gender_restriction === 'female'
                                ? "الفرع ({$branchName}) مخصص للإناث فقط، لا يمكن تعيين مدرب ذكر."
                                : "الفرع ({$branchName}) مخصص للذكور فقط، لا يمكن تعيين مدربة أنثى.";
                            $validator->errors()->add('gender', $msg);
                            break;
                        }
                    }
                }
            }

            if ($this->has('activity_ids') && is_array($this->activity_ids) && $coachId) {
                if ($coach) {
                    $newActivityIds = array_map('intval', $this->activity_ids);
                    $currentActivities = $coach->activities()->get();
                    $removedActivities = $currentActivities->whereNotIn('id', $newActivityIds);

                    if ($removedActivities->isNotEmpty()) {
                        $removedPivotIds = $removedActivities->pluck('pivot.id')->filter()->values();

                        $conflictingPivotIds = \Illuminate\Support\Facades\DB::table('plan_activities')
                            ->join('subscription_plans', 'subscription_plans.id', '=', 'plan_activities.plan_id')
                            ->whereIn('plan_activities.staff_activity_id', $removedPivotIds)
                            ->whereNull('plan_activities.deleted_at')
                            ->whereNull('subscription_plans.deleted_at')
                            ->pluck('plan_activities.staff_activity_id')
                            ->unique()
                            ->all();

                        if (!empty($conflictingPivotIds)) {
                            $conflictNames = $removedActivities
                                ->whereIn('pivot.id', $conflictingPivotIds)
                                ->pluck('name')
                                ->unique()
                                ->values()
                                ->all();

                            $namesString = implode('، ', $conflictNames);
                            $validator->errors()->add(
                                'activity_ids',
                                "لا يمكن فك ارتباط الأنشطة التالية: ({$namesString}) بهذا المدرب، نظراً لوجود فعاليات مرتبطة بها مسبقاً."
                            );
                        }
                    }
                }
            }

            if ($this->has('shifts') && !empty($this->shifts)) {
                if ($this->has('activity_ids')) {
                    $activityIds = $this->activity_ids;
                } else {
                    $activityIds = $coach ? $coach->activities()->pluck('activities.id')->toArray() : [];
                }

                if (empty($activityIds)) {
                    $validator->errors()->add('shifts', 'لا يمكن تحديد شفتات بدون تحديد أنشطة للمدرب.');
                } else {
                    $activities = \Modules\Sports\Models\Activity::whereIn('id', $activityIds)
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
