<?php

namespace Modules\Sports\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ActivityTypeResource",
    title: "Activity Type Resource",
    description: "Activity Type resource representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "object", properties: [
            new OA\Property(property: "ar", type: "string", example: "صالة مفتوحة"),
            new OA\Property(property: "en", type: "string", example: "open_gym")
        ]),
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "is_active", type: "boolean", example: true)
    ]
)]
class ActivityTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'branch_id' => $this->branch_id,
            'is_active' => $this->is_active,
        ];
    }
}
