<?php

namespace Modules\Sports\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SportSessionBookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sports_session_id' => $this->sports_session_id,
            'member_id' => $this->member_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'session' => new SessionResource($this->whenLoaded('session')),
        ];
    }
}
