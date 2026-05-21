<?php

namespace Modules\Sports\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'branch' => $this->branch,
            'activity' => $this->whenLoaded('activity', fn() => new ActivityResource($this->activity)),
            'staff_id' => $this->staff_id,
            'staff' => $this->staff,
            'facility_id' => $this->facility_id,
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'max_players' => $this->max_players,
            'status' => $this->status,
            'booked_count' => $this->booked_count,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
