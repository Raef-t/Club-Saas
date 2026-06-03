<?php

namespace Modules\SubscriptionManager\Console;

use Illuminate\Console\Command;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\MemberManager\Models\Member;
use Modules\NotificationManager\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionStatus extends Command
{
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
    protected $description = 'Scan active subscriptions and transition them to expired if their end date has passed or sessions have run out.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning subscriptions for expiration...');
        Log::info('Subscription status scan started.');

        $notificationService = app(NotificationService::class);

        // 1. Find subscriptions to expire
        $toExpire = PlayerSubscription::where('status', 'active')
            ->where(function ($query) {
                $query->whereNotNull('end_date')->where('end_date', '<=', now()->toDateString())
                    ->orWhere(function ($q) {
                        $q->where('remaining_sessions', '<=', 0)
                          ->whereHas('plan', function ($qp) {
                              $qp->where('type', 'session_based');
                          });
                    });
            })
            ->get();

        $expiredCount = 0;
        foreach ($toExpire as $sub) {
            DB::transaction(function () use ($sub, $notificationService, &$expiredCount) {
                // Update status
                $sub->update(['status' => 'expired']);
                $expiredCount++;

                // Release lockers associated with the subscription
                $rentedLockerIds = $sub->services()
                    ->whereNotNull('locker_id')
                    ->pluck('locker_id')
                    ->toArray();

                if (!empty($rentedLockerIds)) {
                    DB::table('lockers')
                        ->whereIn('id', $rentedLockerIds)
                        ->update([
                            'status' => 'available',
                            'updated_at' => now(),
                        ]);
                }

                // Notify member
                $member = Member::find($sub->member_id);
                if ($member) {
                    $fullName = $member->person_id ? DB::table('people')->where('id', $member->person_id)->value('full_name') : 'Member';
                    try {
                        $notificationService->notifySubscriptionExpired($member, [
                            'name' => $fullName,
                            'plan_name' => $sub->plan->getTranslation('name', app()->getLocale()) ?? 'Plan',
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Failed to send subscription expired notification: " . $e->getMessage());
                    }
                }
            });
        }

        $this->info("Successfully expired {$expiredCount} subscriptions.");
        Log::info("Subscription status scan completed. Expired {$expiredCount} subscriptions.");

        // 2. Find subscriptions expiring in 3 days for alert notification
        $expiringSoon = PlayerSubscription::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '=', now()->addDays(3)->toDateString())
            ->get();

        foreach ($expiringSoon as $sub) {
            $member = Member::find($sub->member_id);
            if ($member) {
                $fullName = $member->person_id ? DB::table('people')->where('id', $member->person_id)->value('full_name') : 'Member';
                try {
                    $notificationService->notifySubscriptionExpiring($member, [
                        'name' => $fullName,
                        'plan_name' => $sub->plan->getTranslation('name', app()->getLocale()) ?? 'Plan',
                        'days_left' => 3,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to send subscription expiring soon notification: " . $e->getMessage());
                }
            }
        }

        return Command::SUCCESS;
    }
}
