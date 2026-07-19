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
            'is_coach' => $this->staff ? $this->staff->isCoach() : false,
            'staff' => new StaffResource($this->whenLoaded('staff')),
            'base_pay' => $this->base_pay,
            'commission_pay' => $this->commission_pay,
            'deductions' => $this->relationLoaded('adjustments') ? $this->adjustments->where('type', 'deduction')->sum('amount') : 0,
            'bonuses' => $this->relationLoaded('adjustments') ? $this->adjustments->where('type', 'bonus')->sum('amount') : 0,
            'adjustments' => $this->whenLoaded('adjustments'),
            'net_pay' => $this->net_pay,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
