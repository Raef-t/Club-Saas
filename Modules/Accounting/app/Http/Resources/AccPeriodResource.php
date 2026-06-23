<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccPeriodResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date'   => $this->end_date?->format('Y-m-d'),
            'status'     => $this->status,
            'closed_at'  => $this->closed_at,
            'notes'      => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
