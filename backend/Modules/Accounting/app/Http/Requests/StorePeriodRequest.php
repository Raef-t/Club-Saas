<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Accounting\Models\AccPeriod;

class StorePeriodRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
            'notes'      => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $startDate = $this->input('start_date');
            $endDate   = $this->input('end_date');

            if ($startDate && $endDate && $endDate > $startDate) {
                $overlap = AccPeriod::where(function ($query) use ($startDate, $endDate) {
                    $query->where('start_date', '<=', $endDate)
                          ->where('end_date', '>=', $startDate);
                })->first();

                if ($overlap) {
                    $validator->errors()->add(
                        'start_date',
                        "تتعارض التواريخ المحددة مع الفترة المحاسبية '{$overlap->name}' ({$overlap->start_date->format('Y-m-d')} إلى {$overlap->end_date->format('Y-m-d')}). لا يُسمح بتداخل الفترات المالية في النظام المحاسبي."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'اسم الفترة المحاسبية مطلوب',
            'start_date.required' => 'تاريخ البداية مطلوب',
            'end_date.required'   => 'تاريخ النهاية مطلوب',
            'end_date.after'      => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
        ];
    }
}
