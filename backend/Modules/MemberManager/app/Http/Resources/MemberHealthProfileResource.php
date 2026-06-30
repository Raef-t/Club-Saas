<?php
namespace Modules\MemberManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MemberHealthProfileResource extends JsonResource
{
    public function toArray($request) {
        return parent::toArray($request);
    }
}
