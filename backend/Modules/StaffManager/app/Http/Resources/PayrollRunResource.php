<?php

namespace Modules\StaffManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'status' => $this->status,
            'payslips' => PayslipResource::collection($this->whenLoaded('payslips')),
            'payslips_count' => $this->whenCounted('payslips'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
