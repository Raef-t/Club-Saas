<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccSafeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'currency'   => $this->currency,
            'is_active'  => $this->is_active,
            'notes'      => $this->notes,
            'account'    => $this->whenLoaded('account', fn() => new AccAccountResource($this->account)),
            'created_at' => $this->created_at,
        ];
    }
}
