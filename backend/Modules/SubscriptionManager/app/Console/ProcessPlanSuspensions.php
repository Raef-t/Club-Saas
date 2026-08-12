<?php

namespace Modules\SubscriptionManager\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\SubscriptionPlanSuspension;

class ProcessPlanSuspensions extends Command
{
    use \Modules\Core\Traits\TracksCommandExecution;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-plan-suspensions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate scheduled plan suspensions and auto-complete finished plan suspensions when end date passes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $period = $today->toDateString();

        if ($this->hasExecutedForPeriod($period)) {
            $this->info("Plan suspensions processing for {$period} was already executed. Skipping.");
            return Command::SUCCESS;
        }

        $this->info('Processing scheduled and active plan suspensions...');

        // 1. Activate scheduled suspensions that reached start date
        $scheduledSuspensions = SubscriptionPlanSuspension::where('status', 'scheduled')
            ->whereDate('suspend_start_date', '<=', $today)
            ->with(['freezes.subscription'])
            ->get();

        $activatedCount = 0;
        foreach ($scheduledSuspensions as $suspension) {
            DB::transaction(function () use ($suspension, &$activatedCount) {
                foreach ($suspension->freezes as $freeze) {
                    $sub = $freeze->subscription;
                    if ($sub && $sub->status === PlayerSubscriptionStatus::ACTIVE) {
                        $sub->update(['status' => PlayerSubscriptionStatus::FROZEN->value]);
                    }
                }
                $suspension->update(['status' => 'active']);
                $activatedCount++;
            });
        }
        $this->info("Activated {$activatedCount} scheduled suspensions.");

        // 2. Complete active suspensions whose end date has passed
        $endedSuspensions = SubscriptionPlanSuspension::where('status', 'active')
            ->whereDate('suspend_end_date', '<', $today)
            ->with(['freezes.subscription'])
            ->get();

        $completedCount = 0;
        foreach ($endedSuspensions as $suspension) {
            DB::transaction(function () use ($suspension, &$completedCount, $today) {
                foreach ($suspension->freezes as $freeze) {
                    $freeze->update(['actual_end_date' => $suspension->suspend_end_date]);
                    $sub = $freeze->subscription;
                    if ($sub && $sub->status === PlayerSubscriptionStatus::FROZEN) {
                        $sub->update(['status' => PlayerSubscriptionStatus::ACTIVE->value]);
                    }
                }
                $suspension->update([
                    'status'          => 'completed',
                    'actual_end_date' => $suspension->suspend_end_date,
                ]);
                $completedCount++;
            });
        }
        $this->info("Completed {$completedCount} ended suspensions.");

        $this->markAsExecuted($period, 'success', [
            'activated' => $activatedCount,
            'completed' => $completedCount,
        ]);

        return Command::SUCCESS;
    }
}
