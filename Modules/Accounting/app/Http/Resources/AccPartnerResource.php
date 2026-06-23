<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccPartnerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'profit_share_pct' => $this->profit_share_pct,
            'joined_at'        => $this->joined_at?->format('Y-m-d'),
            'is_active'        => $this->is_active,
            'notes'            => $this->notes,
            'capital_account'  => $this->whenLoaded('capitalAccount', fn() => new AccAccountResource($this->capitalAccount)),
            'drawings_account' => $this->whenLoaded('drawingsAccount', fn() => new AccAccountResource($this->drawingsAccount)),
            'created_at'       => $this->created_at,
        ];
    }
}
