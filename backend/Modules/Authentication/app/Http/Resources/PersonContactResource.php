<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PersonContactResource",
    title: "Person Contact Resource",
    description: "Person Contact resource representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "person_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "country_code", type: "string", example: "+966"),
        new OA\Property(property: "phone_number", type: "string", example: "500000000"),
        new OA\Property(property: "relation", type: "string", example: "Father", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time")
    ]
)]
class PersonContactResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'name' => $this->name,
            'country_code' => $this->country_code,
            'phone_number' => $this->phone_number,
            'relation' => $this->relation,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
