<?php

namespace Modules\StaffManager\Console;

use Illuminate\Console\Command;
use Modules\Core\Traits\TracksCommandExecution;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\PayrollRun;
use Modules\StaffManager\Models\StaffIncomeEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyPayroll extends Command
{
    use TracksCommandExecution;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:generate-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly payrolls automatically on the 28th of every month.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now();
        
        // Only generate on or after the 28th
        if ($today->day < 28) {
            $this->info('Today is before the 28th. Skipping payroll generation.');
            return Command::SUCCESS;
        }

        $period = $today->format('Y-m'); // e.g. 2026-07

        // Check if we already executed successfully this month using our tracking table
        if ($this->hasExecutedForPeriod($period)) {
            $this->info("Payroll for period {$period} was already generated successfully. Skipping.");
            return Command::SUCCESS;
        }

        $this->info("Starting payroll generation for period {$period}...");
        Log::info("Starting automated payroll generation for period {$period}");

        // Period calculation
        $periodStart = $today->copy()->startOfMonth(); // from 1st
        $periodEnd = $today->copy()->endOfMonth(); // to end of month. Wait, the user might want period_end to be the 28th or 31st. We'll use endOfMonth for standard logic.

        try {
            DB::transaction(function () use ($periodStart, $periodEnd) {
                // 1. Create the PayrollRun
                $payrollRun = PayrollRun::create([
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'status' => 'draft',
                ]);

                // 2. Fetch all staff members
                $staffMembers = Staff::where('is_active', true)->get();

                foreach ($staffMembers as $staff) {
                    $basePay = 0;
                    $commissionPay = 0;

                    // Calculate Base Salary if applicable
                    if (in_array($staff->employment_type, ['fixed_salary', 'hybrid'])) {
                        $basePay = $staff->base_salary ?? 0;
                    }

                    // Calculate Commissions if applicable
                    if (in_array($staff->employment_type, ['hybrid', 'percentage_only', 'commission'])) {
                        $commissionPay = StaffIncomeEntry::where('staff_id', $staff->id)
                            ->where('status', 'pending')
                            ->where('type', 'commission')
                            // We sum all pending commissions regardless of exact date, or bounded by date if preferred.
                            // Usually, you sum all 'pending' up to now.
                            ->where('created_at', '<=', now())
                            ->sum('amount');
                    }
                    
                    // Only create a payslip if there is something to pay
                    if ($basePay > 0 || $commissionPay > 0) {
                        $netPay = $basePay + $commissionPay;
                        
                        $payrollRun->payslips()->create([
                            'staff_id' => $staff->id,
                            'base_pay' => $basePay,
                            'commission_pay' => $commissionPay,
                            'net_pay' => $netPay,
                        ]);

                        // Optional: we can mark those specific income entries as 'linked' to this payslip, 
                        // but since the payslip is 'draft', we wait until 'approval' to mark them as 'paid'.
                        // However, we need to associate them if possible. For now, we rely on the manual approval step.
                    }
                }
            });

            // Mark this command as successfully executed for this period
            $this->markAsExecuted($period);

            // Send notification to super admins
            try {
                $superAdmins = \App\Models\User::role('super_admin')->pluck('id')->toArray();
                if (!empty($superAdmins)) {
                    $notificationService = app(\Modules\NotificationManager\Services\NotificationService::class);
                    $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'payroll_generation_success')->first();

                    if ($template) {
                        $monthName = $today->locale('ar')->translatedFormat('F'); // Arabic month name

                        $body = $template->parseBody([
                            'شهر' => $monthName,
                            'تاريخ_البداية' => $periodStart->toDateString(),
                            'تاريخ_النهاية' => $periodEnd->toDateString(),
                        ]);

                        $notificationService->createNotification([
                            'title' => $template->subject ?? 'تم إعداد مسير الرواتب بنجاح 💰',
                            'body' => $body,
                            'user_ids' => $superAdmins,
                            'sender_type' => 'system'
                        ]);
                    }
                }
            } catch (\Exception $notifyException) {
                Log::error("Failed to send payroll notification to super_admin: " . $notifyException->getMessage());
            }

            $this->info("Payroll generation completed successfully.");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to generate payroll: " . $e->getMessage());
            Log::error("Failed to generate payroll for period {$period}: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
