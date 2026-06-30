<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'code'               => $this->code,
            'name'               => $this->name,
            'name_en'            => $this->name_en,
            'type'               => $this->type,
            'currency'           => $this->currency,
            'parent_id'          => $this->parent_id,
            'is_active'          => $this->is_active,
            'allow_manual_entry' => $this->allow_manual_entry,
            'description'        => $this->description,
            'children'           => $this->whenLoaded('children', fn() => AccAccountResource::collection($this->children)),
        ];
    }
}
