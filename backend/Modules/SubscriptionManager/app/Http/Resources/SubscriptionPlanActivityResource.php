<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanActivityResource extends JsonResource
{
    public function toArray($request)
    {
        $staffActivity = $this->relationLoaded('staffActivity') ? $this->staffActivity : $this->staffActivity;
        $activity = ($staffActivity && $staffActivity->relationLoaded('activity')) ? $staffActivity->activity : ($staffActivity?->activity);
        $activityType = ($activity && $activity->relationLoaded('activityType')) ? $activity->activityType : ($activity?->activityType);
        $staff = ($staffActivity && $staffActivity->relationLoaded('staff')) ? $staffActivity->staff : ($staffActivity?->staff);
        $person = ($staff && $staff->relationLoaded('person')) ? $staff->person : ($staff?->person);

        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'activity_id' => $this->activity_id,
            'activity_name' => $activity?->name,
            'activity_type_id' => $activity?->activity_type_id,
            'activity_type_name' => $activityType?->name,
            'coach_id' => $this->coach_id,
            'coach_name' => $person?->full_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
