<?php
namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionFreezeResource extends JsonResource
{
    public function toArray($request) {
        return parent::toArray($request);
    }
}
