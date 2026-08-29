<?php

namespace Modules\StaffManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'payroll_run_id' => $this->payroll_run_id,
            'staff_id' => $this->staff_id,
            'staff_name' => $this->staff_name ?? $this->staff?->person?->full_name ?? 'Unknown',
            'is_coach' => $this->staff ? $this->staff->isCoach() : false,
            'staff' => new StaffResource($this->whenLoaded('staff')),
            'base_pay' => (float) $this->base_pay,
            'commission_pay' => (float) $this->commission_pay,
            'subscribers_count' => (int) ($this->subscribers_count ?? 0),
            'deductions' => $this->relationLoaded('adjustments') ? (float) $this->adjustments->where('type', 'deduction')->sum('amount') : 0,
            'bonuses' => $this->relationLoaded('adjustments') ? (float) $this->adjustments->where('type', 'bonus')->sum('amount') : 0,
            'adjustments' => $this->whenLoaded('adjustments'),
            'net_pay' => (float) $this->net_pay,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
