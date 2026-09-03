<?php

namespace Modules\StaffManager\Console;

use Illuminate\Console\Command;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\BranchSetting;
use Modules\StaffManager\Models\PayrollRun;
use Modules\StaffManager\Services\PayrollService;
use Modules\NotificationManager\Services\NotificationService;
use Modules\NotificationManager\Models\NotificationTemplate;
use Modules\Authentication\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckPayrollDueDate extends Command
{
    use \Modules\Core\Traits\TracksCommandExecution;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:check-due-date {--force : تجاوز فحص التكرار لليوم}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'فحص وإرسال إشعارات استحقاق توليد الرواتب لمدراء النظام في يوم الاستحقاق المحدد لكل فرع';

    /**
     * Execute the console command.
     */
    public function handle(PayrollService $payrollService, NotificationService $notificationService)
    {
        $today = Carbon::today();
        $period = $today->format('Y-m-d');
        $force = $this->option('force');

        if (!$force && $this->hasExecutedForPeriod($period)) {
            $this->info("Payroll due date check for {$period} was already executed successfully. Skipping.");
            return Command::SUCCESS;
        }

        $this->info("بدء فحص استحقاق توليد الرواتب لتاريخ: {$period}...");
        Log::info("بدء فحص استحقاق توليد الرواتب اليومي ({$period})");

        $branches = Branch::with('settings')->get();
        $notificationsSent = 0;

        foreach ($branches as $branch) {
            $setting = $branch->settings;
            if (!$setting || empty($setting->payroll_end_day)) {
                $this->line("تخطي الفرع [{$branch->id}: {$branch->name}] - لا توجد إعدادات يوم استحقاق الرواتب.");
                continue;
            }

            // 1. التحقق إن كان تاريخ اليوم هو يوم استحقاق الرواتب للفرع
            if ((int)$today->day !== (int)$setting->payroll_end_day) {
                continue;
            }

            // 2. التحقق من عطلات النادي
            if ($payrollService->isHoliday($branch->id, $today)) {
                $this->warn("الفرع [{$branch->id}: {$branch->name}] في عطلة رسمية اليوم. تم إرسال تنبيه عطلة وتخطي التوليد.");
                $payrollService->notifyAdminsHolidayError();
                continue;
            }

            // 3. حساب فترة التوليد والتحقق من عدم وجود رواتب مثبتة مسبقاً
            $calculationDay = (clone $today)->setDay($setting->payroll_end_day);
            $periodEnd = $calculationDay->copy()->subDay()->endOfDay();
            $periodStart = $calculationDay->copy()->subMonthNoOverflow()->startOfDay();

            $existingRun = PayrollRun::where('branch_id', $branch->id)
                ->whereDate('period_start', $periodStart->toDateString())
                ->whereDate('period_end', $periodEnd->toDateString())
                ->first();

            if ($existingRun) {
                $this->line("الفرع [{$branch->id}: {$branch->name}] تم تثبيت رواتبه مسبقاً لهذه الفترة. تخطي الإشعار.");
                continue;
            }

            // 4. جلب معرفات مدراء النظام والفرع
            $adminUserIds = User::where(function ($q) {
                $q->whereIn('role', ['admin', 'super_admin', 'super-admin', 'branch_manager'])
                  ->orWhereHas('roles', function ($rq) {
                      $rq->whereIn('name', ['admin', 'super_admin', 'super-admin', 'branch_manager']);
                  });
            })->pluck('id')->toArray();

            if (empty($adminUserIds)) {
                $this->warn("لا يوجد مدراء مستهدفون لإرسال الإشعار للفرع [{$branch->name}].");
                continue;
            }

            // 5. تجهيز محتوى الإشعار من القالب
            $branchName = $branch->name ?? "الفرع #{$branch->id}";
            $monthName = $today->translatedFormat('F Y') ?: $today->format('m/Y');
            $dueDate = $today->toDateString();

            $template = NotificationTemplate::where('system_key', 'payroll_due_reminder')
                ->where('is_active', true)
                ->first();

            if ($template) {
                $title = str_replace(
                    ['{اسم_الفرع}', '{شهر}', '{تاريخ_الاستحقاق}'],
                    [$branchName, $monthName, $dueDate],
                    $template->subject ?? "تنبيه: موعد توليد الرواتب لفرع {$branchName} 💰"
                );

                $body = $template->parseBody([
                    'اسم_الفرع' => $branchName,
                    'شهر' => $monthName,
                    'تاريخ_الاستحقاق' => $dueDate,
                ]);
            } else {
                $title = "تنبيه: موعد توليد الرواتب لفرع {$branchName} 💰";
                $body = "إدارة النادي الكريمة، اليوم هو موعد توليد رواتب شهر {$monthName} لفرع {$branchName}. يرجى مراجعة الرواتب واعتمادها.";
            }

            // 6. إرسال الإشعار
            $notificationService->createNotification([
                'title' => $title,
                'body' => $body,
                'sender_id' => null,
                'sender_type' => 'system',
                'user_ids' => $adminUserIds,
                'target_snapshot' => [
                    'type' => 'payroll_due',
                    'branch_id' => $branch->id,
                    'branch_name' => $branchName,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'action_url' => "/dashboard/payroll/generate?branch_id={$branch->id}",
                ],
            ]);

            $notificationsSent++;
            $this->info("✅ تم إرسال إشعار استحقاق الرواتب لفرع [{$branchName}] بنجاح إلى " . count($adminUserIds) . " مدير.");
        }

        if (!$force) {
            $this->markAsExecuted($period);
        }

        $this->info("اكتمل فحص الرواتب اليومي بنجاح. تم إرسال {$notificationsSent} إشعار/إشعارات.");
        return Command::SUCCESS;
    }
}
