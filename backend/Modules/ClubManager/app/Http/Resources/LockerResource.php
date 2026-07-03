<?php

namespace Modules\ClubManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LockerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'branch_id'     => $this->branch_id,
            'locker_number' => $this->locker_number,

            // ── Current state ──────────────────────────────────────────────
            'status'        => $this->status,  // available|with_member|with_staff|with_guest

            // ── Holder (polymorphic) ───────────────────────────────────────
            'holder_id'     => $this->holder_id,    // null for guests
            'holder_type'   => $this->holder_type,  // member|staff|guest|null
            'holder_name'   => $this->holder_name,  // display name or guest name
            'assigned_at'   => $this->assigned_at?->toIso8601String(),

            // ── Relations ─────────────────────────────────────────────────
            'branch'        => new BranchResource($this->whenLoaded('branch')),

            'created_at'    => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
