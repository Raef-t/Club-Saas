<?php
namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffActivityRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        $staffActivityId = $this->route('staff_activity');
        $staffActivity = \Modules\Sports\Models\StaffActivity::find($staffActivityId);
        $staffId = $staffActivity ? $staffActivity->staff_id : null;

        return [
            'activity_id' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::unique('staff_activities', 'activity_id')->where(function ($query) use ($staffId) {
                    return $query->where('staff_id', $staffId);
                })->ignore($staffActivityId),
            ],
        ];
    }

    public function messages()
    {
        return [
            'activity_id.unique' => 'هذا الموظف أو المدرب مرتبط مسبقاً بهذا النشاط.',
        ];
    }
}
