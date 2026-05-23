<?php
namespace Modules\StaffManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StaffShiftResource extends JsonResource
{
    public function toArray($request) {
        return parent::toArray($request);
    }
}
