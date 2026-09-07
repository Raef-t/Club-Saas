<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FreezeSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('freeze_start_date') && $this->has('start_date')) {
            $this->merge([
                'freeze_start_date' => $this->input('start_date'),
            ]);
        }

        // If days_count is passed without freeze_end_date, calculate it automatically
        if (!$this->has('freeze_end_date') && ($this->has('days_count') || $this->has('days'))) {
            $startDate = $this->input('freeze_start_date') ?? $this->input('start_date');
            $days = (int) ($this->input('days_count') ?? $this->input('days'));
            if ($startDate && $days > 0) {
                $this->merge([
                    'freeze_end_date' => \Carbon\Carbon::parse($startDate)->addDays($days)->toDateString(),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'freeze_start_date' => 'required|date',
            'freeze_end_date'   => 'nullable|date|after_or_equal:freeze_start_date',
            'reason'            => 'required|string|max:500',
        ];
    }
}
