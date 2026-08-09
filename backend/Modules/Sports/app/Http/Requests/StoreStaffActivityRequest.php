<?php
namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffActivityRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'staff_id' => 'required|integer',
            'activity_id' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::unique('staff_activities', 'activity_id')->where(function ($query) {
                    return $query->where('staff_id', $this->staff_id);
                }),
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
