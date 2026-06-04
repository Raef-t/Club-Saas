<?php

namespace Modules\SubscriptionManager\Listeners;

use Modules\AttendanceManager\Events\CheckInRecorded;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeductSessionOnCheckIn
{
    /**
     * Handle the event.
     *
     * @param CheckInRecorded $event
     * @return void
     */
    public function handle(CheckInRecorded $event): void
    {
        // Only react to member attendance for generic deductions
        if ($event->attendance->attendable_type !== 'member') {
            return;
        }

        $memberId = $event->attendance->attendable_id;

        DB::transaction(function () use ($memberId) {
            // Find the active session-based subscription expiring soonest
            $subscription = PlayerSubscription::where('player_id', $memberId)
                ->where('status', 'active')
                ->whereNotNull('remaining_sessions')
                ->where('remaining_sessions', '>', 0)
                ->orderBy('end_date', 'asc')
                ->lockForUpdate()
                ->first();

            if ($subscription) {
                $subscription->decrement('remaining_sessions');
                $subscription->update(['last_used_at' => now()]);
                Log::info("DeductSessionOnCheckIn: Decremented sessions for Subscription ID {$subscription->id}. Remaining: {$subscription->remaining_sessions}");
            } else {
                Log::warning("DeductSessionOnCheckIn: No active session-based subscription found for Member ID {$memberId}.");
            }
        });
    }
}
