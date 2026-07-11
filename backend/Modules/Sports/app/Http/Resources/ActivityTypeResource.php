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
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "is_session_based", type: "boolean", example: true),
        new OA\Property(property: "has_unlimited_subscribers", type: "boolean", example: false),
        new OA\Property(property: "has_shifts", type: "boolean", example: false)
    ]
)]
class ActivityTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'is_session_based' => $this->is_session_based,
            'has_unlimited_subscribers' => $this->has_unlimited_subscribers,
            'has_shifts' => $this->has_shifts,
        ];
    }
}
