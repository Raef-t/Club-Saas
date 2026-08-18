<?php
namespace Modules\ClubManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClubResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'logo_url'   => $this->logo_url,
            'is_active'  => (bool) $this->is_active,
            'branches'   => BranchResource::collection($this->whenLoaded('branches')),
            'settings'   => $this->whenLoaded('settings'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
