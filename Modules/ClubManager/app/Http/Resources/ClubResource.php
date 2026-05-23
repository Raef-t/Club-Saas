<?php
namespace Modules\ClubManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClubResource extends JsonResource
{
    public function toArray($request) {
        return parent::toArray($request);
    }
}
