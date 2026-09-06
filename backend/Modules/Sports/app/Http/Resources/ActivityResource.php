<?php

namespace Modules\Sports\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ActivityResource",
    title: "Activity Resource",
    description: "Activity resource representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 10),
        new OA\Property(property: "name", type: "string", example: "سباحة مبتدئين"),
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "activity_type", ref: "#/components/schemas/ActivityTypeResource", description: "Activity type details"),
        new OA\Property(property: "is_unlimited_subscribers", type: "boolean", example: false),
        new OA\Property(property: "description", type: "string", nullable: true, example: "تشمل دمج بين التمارين الهوائية والحديد"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-08-01T17:22:24+03:00")
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
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
