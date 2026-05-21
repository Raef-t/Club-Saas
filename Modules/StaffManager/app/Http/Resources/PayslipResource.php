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
            'staff' => new StaffResource($this->whenLoaded('staff')),
            'base_pay' => $this->base_pay,
            'commission_pay' => $this->commission_pay,
            'net_pay' => $this->net_pay,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
