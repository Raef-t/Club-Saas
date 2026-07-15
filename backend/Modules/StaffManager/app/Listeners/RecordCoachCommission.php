<?php

namespace Modules\StaffManager\Listeners;

use Modules\SubscriptionManager\Events\SubscriptionCreated;
use Modules\StaffManager\Models\Staff;

class RecordCoachCommission
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  SubscriptionCreated  $event
     * @return void
     */
    public function handle(SubscriptionCreated $event)
    {
        $subscription = $event->subscription;
        $plan = $event->plan;

        if (!$plan || !$plan->planActivities) {
            return;
        }

        foreach ($plan->planActivities as $planActivity) {
            $coachId = $planActivity->coach_id;

            // Calculate and record commission if applicable
            if ($plan->type === 'session-based' && $coachId) {
                $coach = Staff::with('coachDetail')->find($coachId);
                if ($coach && $coach->coachDetail) {
                    $workTypes = $coach->coachDetail->work_types ?? [];
                    if (is_array($workTypes) && in_array('activities', $workTypes)) {
                        $percentage = $coach->coachDetail->default_commission_rate ?? 0;
                        if ($percentage > 0) {
                            $commissionAmount = ($plan->base_price * $percentage) / 100;
                            
                            $coach->incomeEntries()->create([
                                'type'               => 'commission',
                                'source_type'        => get_class($subscription),
                                'source_id'          => $subscription->id,
                                'base_amount'        => $plan->base_price,
                                'percentage_applied' => $percentage,
                                'amount'             => $commissionAmount,
                                'status'             => 'pending',
                                'description'        => "عمولة اشتراك جلسات للاعب في خطة: {$plan->name}",
                            ]);
                        }
                    }
                }
            }
        }
    }
}
