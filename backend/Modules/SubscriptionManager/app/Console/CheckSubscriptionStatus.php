<?php

namespace Modules\SubscriptionManager\Console;

use Illuminate\Console\Command;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\LockerReservation;
use Modules\ClubManager\Models\Locker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionStatus extends Command
{
    use \Modules\Core\Traits\TracksCommandExecution;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active subscriptions and transition them to expired if their end date has passed or sessions have run out. Also expire locker reservations.';

    /**
     * Execute the console command.
     */
    public function handle(\Modules\SubscriptionManager\Services\SubscriptionNotificationService $notificationService)
    {
        $today = now();
        $period = $today->format('Y-m-d'); // Daily period tracking

        // Check if we already executed successfully today using our tracking table
        if ($this->hasExecutedForPeriod($period)) {
            $this->info("Subscription status check for {$period} was already executed successfully. Skipping.");
            return Command::SUCCESS;
        }

        $this->info('Scanning subscriptions and lockers for expiration...');
        Log::info('Subscription and locker status scan started.');

        // Send expiration warnings (3 days before)
        $this->info('Sending expiration warning notifications...');
        $notificationService->sendUpcomingExpirationWarnings(3);

        $today = now()->toDateString();

        // 1. Find subscriptions to expire by date
        $toExpireByDate = PlayerSubscription::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->get();

        // 2. Find subscriptions to expire by sessions (all items fully consumed)
        $activeSubscriptions = PlayerSubscription::with('items')->where('status', 'active')->get();
        $toExpireBySessions = collect();

        foreach ($activeSubscriptions as $sub) {
            if ($sub->items->isEmpty()) {
                continue;
            }

            $allConsumed = true;
            foreach ($sub->items as $item) {
                if ($item->is_unlimited || $item->sessions_consumed < $item->sessions_allocated) {
                    $allConsumed = false;
                    break;
                }
            }

            if ($allConsumed) {
                $toExpireBySessions->push($sub);
            }
        }

        // Merge both collections
        $toExpire = $toExpireByDate->merge($toExpireBySessions)->unique('id');

        /** @var \Modules\SubscriptionManager\Services\SubscriptionService $subscriptionService */
        $subscriptionService = app(\Modules\SubscriptionManager\Services\SubscriptionService::class);
        $expiredCount = 0;
        foreach ($toExpire as $sub) {
            DB::transaction(function () use ($sub, &$expiredCount, $subscriptionService) {
                /** @var \Modules\SubscriptionManager\Services\SubscriptionService $subscriptionService */
                // Update status
                $sub->update(['status' => \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::FINISHED->value]);
                $subscriptionService->decrementPlanSubscribers($sub->plan);
                $expiredCount++;
            });
        }

        $this->info("Successfully expired {$expiredCount} subscriptions.");
        Log::info("Subscription status scan completed. Expired {$expiredCount} subscriptions.");

        // 3. Find locker reservations to expire by date
        $expiredLockersCount = 0;
        $toExpireLockers = LockerReservation::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->get();

        foreach ($toExpireLockers as $reservation) {
            DB::transaction(function () use ($reservation, &$expiredLockersCount) {
                $reservation->update(['status' => 'expired']); // or completed/cancelled based on your status enum

                $locker = Locker::find($reservation->locker_id);
                if ($locker) {
                    $locker->update(['status' => 'available']);
                }
                $expiredLockersCount++;
            });
        }

        $this->info("Successfully expired {$expiredLockersCount} locker reservations.");
        Log::info("Locker reservation status scan completed. Expired {$expiredLockersCount} reservations.");

        // Mark this command as successfully executed for this period
        $this->markAsExecuted($period);

        return Command::SUCCESS;
    }
}
