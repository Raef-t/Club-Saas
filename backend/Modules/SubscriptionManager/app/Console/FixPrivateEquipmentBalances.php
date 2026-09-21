<?php

namespace Modules\SubscriptionManager\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\Invoice;
use Modules\SubscriptionManager\Models\Payment;
use Modules\SubscriptionManager\Models\SubscriptionRevenueSplit;

class FixPrivateEquipmentBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:fix-private-equipment-balances {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes historic balance discrepancies for private equipment subscriptions (148, 149, 224, 111, 112, 114).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? "Running in DRY RUN mode (no changes will be written)..." : "Applying balance fixes in transaction...");

        DB::beginTransaction();

        try {
            // 1. روان مزكتلي (ID 149) & علا ناصر (ID 224)
            // Fix payment allocations: club safe = 275.00, coach outside safe = 225.00, total paid = 500.00, remaining = 0.00
            $splitFixSubs = [
                149 => ['club_payment_id' => 169, 'coach_payment_id' => 268],
                224 => ['club_payment_id' => 264, 'coach_payment_id' => 269],
            ];

            foreach ($splitFixSubs as $subId => $config) {
                $sub = PlayerSubscription::find($subId);
                if (!$sub) {
                    $this->warn("Subscription ID {$subId} not found, skipping.");
                    continue;
                }

                $this->line("Fixing Subscription #{$subId} ({$sub->member?->person?->full_name}):");
                $this->line("  Old: Total={$sub->total_amount}, Paid={$sub->paid_amount}, Remaining={$sub->remaining_amount}");

                $clubPmt = Payment::find($config['club_payment_id']);
                if ($clubPmt) {
                    $this->line("  Updating Club Payment #{$clubPmt->id}: amount {$clubPmt->amount} -> 275.00 (Safe #{$clubPmt->safe_id})");
                    if (!$dryRun) {
                        $clubPmt->update(['amount' => 275.00]);
                    }
                }

                $coachPmt = Payment::find($config['coach_payment_id']);
                if ($coachPmt) {
                    $this->line("  Updating Coach Payment #{$coachPmt->id}: amount {$coachPmt->amount} -> 225.00 (Safe NULL)");
                    if (!$dryRun) {
                        $coachPmt->update(['amount' => 225.00]);
                    }
                }

                if (!$dryRun) {
                    $sub->update([
                        'paid_amount' => 500.00,
                        'remaining_amount' => 0.00,
                    ]);

                    Invoice::where('player_subscription_id', $subId)->update([
                        'total' => 500.00,
                        'status' => 'paid',
                    ]);

                    SubscriptionRevenueSplit::where('player_subscription_id', $subId)->update([
                        'total_amount' => 500.00,
                        'coach_amount' => 225.00,
                        'club_amount' => 275.00,
                    ]);
                }
                $this->info("  New: Total=500.00, Paid=500.00, Remaining=0.00 [Status: Paid]");
            }

            // 2. صبا العمر (ID 148)
            // Has 12 sessions allocated (Plan 32, 500.00), paid 275 (Safe 2) + 225 (Coach) = 500.00.
            // Plan total was 650.00, leaving remaining 150.00.
            $sub148 = PlayerSubscription::find(148);
            if ($sub148) {
                $this->line("Fixing Subscription #148 ({$sub148->member?->person?->full_name}):");
                $this->line("  Old: Total={$sub148->total_amount}, Paid={$sub148->paid_amount}, Remaining={$sub148->remaining_amount}");

                if (!$dryRun) {
                    $sub148->update([
                        'total_amount' => 500.00,
                        'paid_amount' => 500.00,
                        'remaining_amount' => 0.00,
                    ]);

                    Invoice::where('player_subscription_id', 148)->update([
                        'total' => 500.00,
                        'status' => 'paid',
                    ]);

                    SubscriptionRevenueSplit::where('player_subscription_id', 148)->update([
                        'total_amount' => 500.00,
                        'coach_amount' => 225.00,
                        'club_amount' => 275.00,
                    ]);
                }
                $this->info("  New: Total=500.00, Paid=500.00, Remaining=0.00 [Status: Paid]");
            }

            // 3. Excess Paid Subscriptions: روان سودة (111), بيان حريري (112), هالا شحيبر (114)
            // Adjust plan total_amount to equal the paid amount so no excess is recorded.
            $excessSubs = [
                111 => 350.00,
                112 => 350.00,
                114 => 275.00,
            ];

            foreach ($excessSubs as $subId => $correctTotal) {
                $sub = PlayerSubscription::find($subId);
                if (!$sub) {
                    $this->warn("Subscription ID {$subId} not found, skipping.");
                    continue;
                }

                $this->line("Fixing Excess Subscription #{$subId} ({$sub->member?->person?->full_name}):");
                $this->line("  Old: Total={$sub->total_amount}, Paid={$sub->paid_amount}, Remaining={$sub->remaining_amount}");

                if (!$dryRun) {
                    $sub->update([
                        'total_amount' => $correctTotal,
                        'paid_amount' => $correctTotal,
                        'remaining_amount' => 0.00,
                    ]);

                    Invoice::where('player_subscription_id', $subId)->update([
                        'total' => $correctTotal,
                        'status' => 'paid',
                    ]);

                    SubscriptionRevenueSplit::where('player_subscription_id', $subId)->update([
                        'total_amount' => $correctTotal,
                    ]);
                }
                $this->info("  New: Total={$correctTotal}, Paid={$correctTotal}, Remaining=0.00 [Status: Paid]");
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn("Dry run completed. No changes were committed to database.");
            } else {
                DB::commit();
                $this->info("All subscription balances successfully rectified and committed.");
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Error occurred while fixing balances: " . $e->getMessage());
            Log::error("FixPrivateEquipmentBalances failed: " . $e->getMessage(), ['exception' => $e]);
            return Command::FAILURE;
        }
    }
}
