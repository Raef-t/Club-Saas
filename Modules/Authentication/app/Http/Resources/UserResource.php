<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'is_active' => (bool) $this->is_active,
            'person' => new PersonResource($this->whenLoaded('person')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
