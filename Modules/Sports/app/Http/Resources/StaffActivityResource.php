<?php
namespace Modules\Sports\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StaffActivityResource extends JsonResource
{
    public function toArray($request) {
        return parent::toArray($request);
    }
}
