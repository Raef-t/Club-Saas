<?php

namespace Modules\Sports\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
        ];
    }
}
