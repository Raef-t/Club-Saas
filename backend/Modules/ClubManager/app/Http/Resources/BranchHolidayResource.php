<?php

namespace Modules\ClubManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "BranchHolidayResource",
    title: "Branch Holiday Resource",
    description: "Branch holiday resource representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "type", type: "string", description: "weekly or specific_dates", example: "specific_dates"),
        new OA\Property(property: "day_of_week", type: "integer", nullable: true, description: "0 for Sunday, 6 for Saturday", example: 5),
        new OA\Property(property: "start_date", type: "string", format: "date", nullable: true, example: "2026-08-10"),
        new OA\Property(property: "end_date", type: "string", format: "date", nullable: true, example: "2026-08-15"),
        new OA\Property(property: "reason", type: "string", nullable: true, example: "Maintenance"),
        new OA\Property(property: "created_at", type: "string", format: "date-time")
    ]
)]
class BranchHolidayResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'type' => $this->type,
            'day_of_week' => $this->day_of_week,
            'start_date' => $this->start_date ? $this->start_date->toDateString() : null,
            'end_date' => $this->end_date ? $this->end_date->toDateString() : null,
            'reason' => $this->reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
