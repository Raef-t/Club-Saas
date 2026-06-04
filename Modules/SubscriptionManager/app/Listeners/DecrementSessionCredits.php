<?php

namespace Modules\SubscriptionManager\Listeners;

use Modules\AttendanceManager\Events\CheckInRecorded;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Illuminate\Support\Facades\Log;

class DecrementSessionCredits
{
    /**
     * Handle the event.
     *
     * @param CheckInRecorded $event
     * @return void
     */
    public function handle(CheckInRecorded $event): void
    {
        // Only react to player subscriptions
        if ($event->attendance->attendable_type !== 'player_subscription') {
            return;
        }

        $attendance = $event->attendance;

        /** @var PlayerSubscription|null $subscription */
        $subscription = PlayerSubscription::find($attendance->attendable_id);

        if (!$subscription) {
            Log::warning("DecrementSessionCredits: Subscription ID {$attendance->attendable_id} not found.");
            return;
        }

        // Decrement remaining sessions if they are tracked (non-null)
        if ($subscription->remaining_sessions !== null && $subscription->remaining_sessions > 0) {
            $subscription->decrement('remaining_sessions');
            
            Log::info("Subscription ID {$subscription->id}: Decremented session credits. New balance: {$subscription->remaining_sessions}");
        }
    }
}
