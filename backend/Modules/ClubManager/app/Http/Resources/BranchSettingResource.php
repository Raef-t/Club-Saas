<?php

namespace Modules\ClubManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "BranchSettingResource",
    title: "Branch Setting Resource",
    description: "Branch settings representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "working_hours_start", type: "string", format: "time", nullable: true, example: "08:00:00"),
        new OA\Property(property: "working_hours_end", type: "string", format: "time", nullable: true, example: "22:00:00"),
        new OA\Property(property: "default_club_commission_percentage", type: "number", format: "float", nullable: true, example: 40.00),
        new OA\Property(property: "default_coach_commission_percentage", type: "number", format: "float", nullable: true, example: 60.00),
        new OA\Property(property: "default_employee_salary", type: "number", format: "float", nullable: true, example: 3500.00),
        new OA\Property(property: "daily_entry_price", type: "number", format: "float", nullable: true, example: 50.00),
        new OA\Property(property: "updated_at", type: "string", format: "date-time")
    ]
)]
class BranchSettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'working_hours_start' => $this->working_hours_start ? substr($this->working_hours_start, 0, 5) : null,
            'working_hours_end' => $this->working_hours_end ? substr($this->working_hours_end, 0, 5) : null,
            'default_club_commission_percentage' => $this->default_club_commission_percentage,
            'default_coach_commission_percentage' => $this->default_coach_commission_percentage,
            'default_employee_salary' => $this->default_employee_salary,
            'daily_entry_price' => $this->daily_entry_price,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
