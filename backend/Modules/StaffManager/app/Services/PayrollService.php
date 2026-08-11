<?php

namespace Modules\StaffManager\Services;

use Modules\StaffManager\Models\Payslip;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\PayrollRun;
use Modules\StaffManager\Models\StaffContract;
use Modules\ClubManager\Models\BranchSetting;
use Modules\ClubManager\Models\BranchHoliday;
use Modules\SubscriptionManager\Models\PlayerSubscriptionItem;
use Modules\NotificationManager\Models\NotificationTemplate;
use Modules\NotificationManager\Services\NotificationService;
use Modules\Authentication\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PayrollService
{
    /**
     * Checks if a given date is a holiday for the branch.
     */
    public function isHoliday(int $branchId, Carbon $date): bool
    {
        $holidays = BranchHoliday::where('branch_id', $branchId)->get();
        foreach ($holidays as $holiday) {
            if ($holiday->type === 'weekly' && $holiday->day_of_week == $date->dayOfWeek) {
                return true;
            }
            if ($holiday->type === 'specific_dates' && $date->between($holiday->start_date, $holiday->end_date)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calculate fixed or hybrid salary base.
     */
    public function calculateFixedSalary(StaffContract $contract, Carbon $periodStart, Carbon $periodEnd, int $totalDaysInPeriod, Carbon $staffStartDate): float
    {
        if (!in_array($contract->employment_type, ['fixed_salary', 'hybrid'])) {
            return 0;
        }

        if ($staffStartDate->greaterThan($periodStart)) {
            $daysWorked = $staffStartDate->diffInDays($periodEnd) + 1;
            if ($daysWorked < 0) $daysWorked = 0;
            if ($daysWorked > $totalDaysInPeriod) $daysWorked = $totalDaysInPeriod;
            return ($contract->base_salary / $totalDaysInPeriod) * $daysWorked;
        }

        return (float) ($contract->base_salary ?? 0);
    }

    /**
     * Calculate commission pay based on subscriptions.
     */
    public function calculateCommissionPay(int $staffId, StaffContract $contract, Carbon $periodStart, Carbon $periodEnd, BranchSetting $branchSetting): float
    {
        if (!in_array($contract->employment_type, ['commission_based', 'hybrid'])) {
            return 0;
        }

        $staff = Staff::with('coachDetail')->find($staffId);
        $rate = $contract->commission_rate ?: ($staff?->coachDetail?->default_commission_rate ?? 0);

        if ($rate <= 0) {
            return 0;
        }

        $commissionPay = 0;

        $items = PlayerSubscriptionItem::with('subscription')
            ->where('coach_id', $staffId)
            ->where('sessions_consumed', '>', 0)
            ->get();

        foreach ($items as $item) {
            $sub = $item->subscription;
            if (!$sub) continue;

            $subStartDate = Carbon::parse($sub->start_date);
            
            if ($subStartDate->between($periodStart, $periodEnd)) {
                $validStatus = false;
                $statusValue = is_object($sub->status) ? $sub->status->value : $sub->status;

                if (in_array($statusValue, ['active', 'frozen'])) {
                    $validStatus = true;
                } elseif ($statusValue === 'terminated' && $branchSetting->include_terminated_subscriptions) {
                    $validStatus = true;
                }

                if ($validStatus) {
                    $commissionPay += ($sub->total_amount * ($rate / 100));
                }
            }
        }

        return $commissionPay;
    }

    /**
     * Generate the payroll preview array.
     */
    public function generatePreview(int $branchId)
    {
        $branchSetting = BranchSetting::where('branch_id', $branchId)->first();
        if (!$branchSetting || !$branchSetting->payroll_start_day || !$branchSetting->payroll_end_day) {
            throw new Exception('إعدادات حساب الرواتب (payroll_start_day, payroll_end_day) غير مكتملة لهذا الفرع.');
        }

        $today = now();

        if ($this->isHoliday($branchId, $today)) {
            $this->notifySuperAdminsHolidayError();
            throw new Exception('لا يمكن حساب الرواتب لتاريخ اليوم، نرجو إعادة المحاولة في يوم عمل آخر لأنه يوم عطلة في النادي.', 400);
        }

        if ($today->day < $branchSetting->payroll_end_day) {
            throw new Exception("لا يمكن حساب الراتب في الوقت الحالي. التواريخ المسموحة تبدأ من يوم {$branchSetting->payroll_end_day} من كل شهر.", 400);
        }

        $periodEnd = clone $today;
        $periodEnd->setDay($branchSetting->payroll_end_day)->subDay()->endOfDay();
        
        $periodStart = clone $today;
        $periodStart->setDay($branchSetting->payroll_start_day)->startOfDay();
        if ($branchSetting->payroll_start_day > $branchSetting->payroll_end_day) {
            $periodStart->subMonth();
        }

        $existingRun = PayrollRun::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        if ($existingRun) {
            throw new Exception("تم حساب وحفظ الرواتب مسبقاً لهذه الفترة (Run ID: {$existingRun->id}).", 409);
        }

        $staffMembers = Staff::where('is_active', true)
            ->where('work_status', 'active')
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->with(['activeContract', 'person', 'coachDetail'])->get();

        $totalDaysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
        if ($totalDaysInPeriod <= 0) $totalDaysInPeriod = 1;

        $payslipsData = [];
        $staffCount = 0;

        foreach ($staffMembers as $staff) {
            $contract = $staff->activeContract;
            if (!$contract) continue;

            $staffStartDate = Carbon::parse($staff->start_date);
            $basePay = $this->calculateFixedSalary($contract, $periodStart, $periodEnd, $totalDaysInPeriod, $staffStartDate);
            $commissionPay = $this->calculateCommissionPay($staff->id, $contract, $periodStart, $periodEnd, $branchSetting);

            $payslipsData[] = [
                'staff_id' => $staff->id,
                'staff_name' => $staff->person?->fullName ?? 'Unknown',
                'base_pay' => round($basePay, 2),
                'commission_pay' => round($commissionPay, 2),
                'deductions' => 0, // Fallbacks for frontend
                'bonuses' => 0,    // Fallbacks for frontend
                'net_pay' => round($basePay + $commissionPay, 2),
                'adjustments' => []
            ];
            $staffCount++;
        }

        return [
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'staff_count' => $staffCount,
            'payslips' => $payslipsData,
        ];
    }

    /**
     * Confirm and save payroll run.
     */
    public function confirmPayroll(array $data)
    {
        return DB::transaction(function () use ($data) {
            $existingRun = PayrollRun::where('period_start', $data['period_start'])
                ->where('period_end', $data['period_end'])
                ->lockForUpdate()
                ->first();

            if ($existingRun) {
                throw new Exception("تم تثبيت رواتب هذه الفترة مسبقاً.", 409);
            }

            $payrollRun = PayrollRun::create([
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'status' => 'draft',
            ]);

            foreach ($data['payslips'] as $payslipData) {
                $payslip = $payrollRun->payslips()->create([
                    'staff_id' => $payslipData['staff_id'],
                    'base_pay' => $payslipData['base_pay'],
                    'commission_pay' => $payslipData['commission_pay'],
                    'net_pay' => $payslipData['net_pay'],
                ]);

                if (!empty($payslipData['adjustments'])) {
                    foreach ($payslipData['adjustments'] as $adj) {
                        $payslip->adjustments()->create([
                            'type' => $adj['type'],
                            'amount' => $adj['amount'],
                            'reason' => $adj['reason'] ?? null,
                        ]);
                    }
                }
            }

            $this->notifySuperAdminsSuccess($data['period_start'], $data['period_end']);
        });
    }

    protected function notifySuperAdminsHolidayError()
    {
        $superAdmins = User::role('super_admin')->pluck('id')->toArray();
        if (empty($superAdmins)) return;

        $template = NotificationTemplate::where('system_key', 'payroll_generation_holiday_error')->first();
        if ($template) {
            app(NotificationService::class)->createNotification([
                'title' => $template->subject ?? 'خطأ في إعداد الرواتب',
                'body' => $template->parseBody([]),
                'sender_id' => null,
                'sender_type' => 'system',
                'user_ids' => $superAdmins,
            ]);
        }
    }

    protected function notifySuperAdminsSuccess($periodStart, $periodEnd)
    {
        $superAdmins = User::role('super_admin')->pluck('id')->toArray();
        if (empty($superAdmins)) return;

        $template = NotificationTemplate::where('system_key', 'payroll_generation_success')->first();
        if ($template) {
            $body = $template->parseBody([
                'شهر' => Carbon::parse($periodStart)->format('Y-m'),
                'تاريخ_البداية' => $periodStart,
                'تاريخ_النهاية' => $periodEnd,
            ]);

            app(NotificationService::class)->createNotification([
                'title' => $template->subject ?? 'إعداد مسير الرواتب',
                'body' => $body,
                'sender_id' => null,
                'sender_type' => 'system',
                'user_ids' => $superAdmins,
            ]);
        }
    }

    /**
     * Get all payroll runs.
     */
    public function getAllPayrollRuns()
    {
        return PayrollRun::with('payslips')->latest()->get();
    }

    /**
     * Get payroll run by ID.
     */
    public function getPayrollRunById(int $id)
    {
        return PayrollRun::with(['payslips.staff.person', 'payslips.adjustments'])->findOrFail($id);
    }

    /**
     * Create a new payroll run draft.
     */
    public function createPayrollRun(string $periodStart, string $periodEnd)
    {
        return PayrollRun::create([
            'period_start' => $periodStart,
            'period_end'   => $periodEnd,
            'status'       => 'draft',
        ]);
    }

    /**
     * Generate/load payslips for a payroll run.
     */
    public function generatePayslips(int $id)
    {
        $run = PayrollRun::findOrFail($id);
        return $run->load('payslips');
    }

    /**
     * Approve a payroll run.
     */
    public function approvePayrollRun(int $id)
    {
        $run = PayrollRun::findOrFail($id);
        $run->update(['status' => 'approved']);
        return $run;
    }
}
