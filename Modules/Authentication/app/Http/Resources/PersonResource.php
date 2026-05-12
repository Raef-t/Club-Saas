<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'type' => $this->type,
            'gender' => $this->gender,
            'dob' => $this->dob,
            'age' => $this->age, // Assuming we have an age attribute/accessor
            'contact' => [
                'mobile_1' => $this->mobile_1,
                'email' => $this->email,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
