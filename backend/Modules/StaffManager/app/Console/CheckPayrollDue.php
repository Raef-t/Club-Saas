<?php

namespace Modules\StaffManager\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\ClubManager\Models\BranchSetting;
use Modules\StaffManager\Services\PayrollService;
use Modules\Core\Models\CommandExecution;

class CheckPayrollDue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'staff:check-payroll-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'فحص استحقاق مسيرات الرواتب للفروع وإرسال إشعار للإدارة في أول يوم عمل متاح بعد تاريخ الاستحقاق';

    /**
     * Execute the console command.
     */
    public function handle(PayrollService $payrollService): int
    {
        $today = Carbon::today();
        $this->info("بدء فحص استحقاق الرواتب لتاريخ: {$today->toDateString()}");

        $branchSettings = BranchSetting::whereNotNull('payroll_end_day')->get();

        if ($branchSettings->isEmpty()) {
            $this->info('لا توجد إعدادات رواتب للفروع.');
            return Command::SUCCESS;
        }

        foreach ($branchSettings as $setting) {
            $branchId = (int) $setting->branch_id;
            $dueDay = (int) $setting->payroll_end_day;

            // 1. الشرط الأول: هل وصلنا ليوم الاستحقاق أو تجاوزناه؟
            if ($today->day < $dueDay) {
                continue;
            }

            // حساب تواريخ فترة الرواتب للشهر الحالي
            $periodEnd = (clone $today)->setDay($dueDay)->endOfDay();
            $periodStart = (clone $periodEnd)->subMonthNoOverflow()->startOfDay();
            $periodKey = "branch_{$branchId}_{$periodStart->toDateString()}_{$periodEnd->toDateString()}";

            // 2. الشرط الثاني: التأكد من أنه لم يتم إرسال الإشعار مسبقاً لهذه الدورة الشهرية
            $alreadySent = CommandExecution::where('command_signature', 'staff:check-payroll-due')
                ->where('period', $periodKey)
                ->where('status', 'success')
                ->exists();

            if ($alreadySent) {
                continue; // تم إرسال الإشعار مسبقاً لهذا الشهر
            }

            // 3. الشرط الثالث: فحص هل اليوم عطلة في هذا الفرع؟
            if ($payrollService->isHoliday($branchId, $today)) {
                $this->warn("الفرع #{$branchId}: اليوم عطلة، سيتم تأجيل الإرسال لأول يوم عمل قادم.");
                continue; // عدم الإرسال اليوم والانتظار لليوم القادم
            }

            // 4. إرسال الإشعار للأدمن
            $payrollService->notifySuperAdminsSuccess($periodStart->toDateString(), $periodEnd->toDateString());

            // 5. تسجيل العملية لمنع التكرار نهائياً في هذا الشهر
            CommandExecution::create([
                'command_signature' => 'staff:check-payroll-due',
                'period' => $periodKey,
                'executed_at' => now(),
                'status' => 'success',
            ]);

            $this->info("الفرع #{$branchId}: تم إرسال إشعار مسير الرواتب بنجاح وتسجيله لمنع التكرار.");
        }

        $this->info('اكتمل فحص استحقاق الرواتب بنجاح.');
        return Command::SUCCESS;
    }
}
