<?php

namespace Modules\Sports\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ActivityResource",
    title: "Activity Resource",
    description: "Activity resource representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "object", properties: [
            new OA\Property(property: "ar", type: "string", example: "سباحة"),
            new OA\Property(property: "en", type: "string", example: "Swimming")
        ]),
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "activity_type", type: "object", description: "Activity type details"),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "is_private_equipment", type: "boolean", example: false),
        new OA\Property(property: "is_unlimited_subscribers", type: "boolean", example: true),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time")
    ]
)]
class ActivityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'branch_id' => $this->branch_id,
            'activity_type' => new ActivityTypeResource($this->activityType),
            'is_unlimited_subscribers' => (bool) ($this->activityType?->has_unlimited_subscribers ?? $this->hasUnlimitedSubscribers()),
            'description' => $this->description,
            'is_private_equipment' => $this->is_private_equipment,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
