<?php

namespace Modules\StaffManager\Services;

use Modules\StaffManager\Models\Payslip;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\PayrollRun;
use Modules\StaffManager\Models\StaffContract;
use Modules\ClubManager\Models\BranchSetting;
use Modules\ClubManager\Models\BranchHoliday;
use Modules\SubscriptionManager\Models\PlayerSubscription;
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
    public function calculateFixedSalary(StaffContract $contract): float
    {
        if (!in_array($contract->employment_type, ['fixed_salary', 'hybrid'])) {
            return 0;
        }

        return (float) ($contract->base_salary ?? 0);
    }

    /**
     * Calculate commission pay based on subscriptions.
     * Returns an array with ['amount' => float, 'subscribers_count' => int]
     */
    public function calculateCommissionPay(int $staffId, StaffContract $contract, Carbon $periodStart, Carbon $periodEnd, BranchSetting $branchSetting, ?Staff $staff = null): array
    {
        if (!in_array($contract->employment_type, ['commission_based', 'hybrid'])) {
            return ['amount' => 0.0, 'subscribers_count' => 0];
        }

        $rate = $contract->commission_rate;
        if (!$rate) {
            $staffModel = $staff ?? Staff::with('coachDetail')->find($staffId);
            $rate = $staffModel?->coachDetail?->default_commission_rate ?? 0;
        }

        if ($rate <= 0) {
            return ['amount' => 0.0, 'subscribers_count' => 0];
        }

        $commissionPay = 0;
        $subscriberMemberIds = [];

        $subscriptions = PlayerSubscription::whereHas('plan.planActivities.staffActivity', function ($q) use ($staffId) {
                $q->where('staff_id', $staffId);
            })
            ->whereHas('items', function ($q) {
                $q->where('sessions_consumed', '>', 0);
            })
            ->get();

        foreach ($subscriptions as $sub) {
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
                    $subscriberMemberIds[] = $sub->member_id;
                }
            }
        }

        return [
            'amount' => (float) $commissionPay,
            'subscribers_count' => count(array_unique($subscriberMemberIds)),
        ];
    }

    /**
     * Generate the payroll preview array.
     */
    public function generatePreview(int $branchId)
    {
        $branchSetting = BranchSetting::where('branch_id', $branchId)->first();
        if (!$branchSetting || !$branchSetting->payroll_end_day) {
            throw new Exception('إعدادات حساب الرواتب (payroll_end_day) غير مكتملة لهذا الفرع.');
        }

        $today = now();

        if ($this->isHoliday($branchId, $today)) {
            $this->notifyAdminsHolidayError();
            throw new Exception('لا يمكن حساب الرواتب لتاريخ اليوم، نرجو إعادة المحاولة في يوم عمل آخر لأنه يوم عطلة في النادي.', 400);
        }

        if ($today->day < $branchSetting->payroll_end_day) {
            throw new Exception("لا يمكن حساب الراتب في الوقت الحالي. التواريخ المسموحة تبدأ من يوم {$branchSetting->payroll_end_day} من كل شهر.", 400);
        }

        $periodEnd = (clone $today)->setDay($branchSetting->payroll_end_day)->endOfDay();
        $periodStart = (clone $periodEnd)->subMonthNoOverflow()->startOfDay();

        $existingRun = PayrollRun::where('branch_id', $branchId)
            ->where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        if ($existingRun) {
            throw new Exception("تم توليد وتثبيت رواتب هذه الفترة مسبقاً لهذا الفرع ولا يمكن إعادة التوليد.", 409);
        }

        $staffMembers = Staff::where('is_active', true)
            ->where('work_status', 'active')
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->with(['activeContract', 'person', 'coachDetail'])->get();

        $commissionPeriodEnd = $periodEnd->copy()->subDay()->endOfDay();

        $payslipsData = [];
        $staffCount = 0;

        foreach ($staffMembers as $staff) {
            $contract = $staff->activeContract;
            if (!$contract) continue;

            $basePay = $this->calculateFixedSalary($contract);
            $commissionResult = $this->calculateCommissionPay($staff->id, $contract, $periodStart, $commissionPeriodEnd, $branchSetting, $staff);
            $commissionPay = $commissionResult['amount'];
            $subscribersCount = $commissionResult['subscribers_count'];


            // Check for unlinked salary advances in accounting during this period
            $advances = \Modules\Accounting\Models\AccSalaryPayment::where('staff_id', $staff->id)
                ->where('payment_type', 'advance')
                ->whereNull('payslip_id')
                ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->get();

            $autoAdjustments = [];
            $totalDeductions = 0;
            foreach ($advances as $adv) {
                $voucherText = $adv->journal_id ? " (سند #{$adv->journal_id})" : "";
                $autoAdjustments[] = [
                    'type' => 'deduction',
                    'amount' => round($adv->amount, 2),
                    'reason' => "سلفة مصروفة مسبقاً{$voucherText} بتاريخ {$adv->date->toDateString()}",
                ];
                $totalDeductions += (float) $adv->amount;
            }

            $calculatedNet = max(0, ($basePay + $commissionPay) - $totalDeductions);

            $payslipsData[] = [
                'staff_id' => $staff->id,
                'staff_name' => $staff->person?->fullName ?? 'Unknown',
                'base_pay' => round($basePay, 2),
                'commission_pay' => round($commissionPay, 2),
                'subscribers_count' => $subscribersCount,
                'deductions' => round($totalDeductions, 2),
                'bonuses' => 0,
                'net_pay' => round($calculatedNet, 2),
                'adjustments' => $autoAdjustments,
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
            $branchId = $data['branch_id'] ?? null;
            $existingRunQuery = PayrollRun::where('period_start', $data['period_start'])
                ->where('period_end', $data['period_end']);

            if ($branchId) {
                $existingRunQuery->where('branch_id', $branchId);
            }

            $existingRun = $existingRunQuery->lockForUpdate()->first();

            if ($existingRun) {
                throw new Exception("تم تثبيت رواتب هذه الفترة مسبقاً لهذا الفرع.", 409);
            }

            $payrollRun = PayrollRun::create([
                'branch_id' => $branchId,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'status' => 'draft',
            ]);

            foreach ($data['payslips'] as $payslipData) {
                $payslip = $payrollRun->payslips()->create([
                    'staff_id' => $payslipData['staff_id'],
                    'staff_name' => $payslipData['staff_name'] ?? null,
                    'base_pay' => $payslipData['base_pay'],
                    'commission_pay' => $payslipData['commission_pay'],
                    'subscribers_count' => $payslipData['subscribers_count'] ?? 0,
                    'net_pay' => $payslipData['net_pay'],
                    'status' => 'pending',
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
        });
    }

    public function notifyAdminsHolidayError()
    {
        $admins = User::role('admin')->pluck('id')->toArray();
        if (empty($admins)) return;

        $template = NotificationTemplate::where('system_key', 'payroll_generation_holiday_error')->first();
        if ($template) {
            app(NotificationService::class)->createNotification([
                'title' => $template->subject ?? 'خطأ في إعداد الرواتب',
                'body' => $template->parseBody([]),
                'sender_id' => null,
                'sender_type' => 'system',
                'user_ids' => $admins,
            ]);
        }
    }


    /**
     * Get all payroll runs.
     */
    public function getAllPayrollRuns(array $filters = [])
    {
        $query = PayrollRun::with('payslips')->latest();

        if ((isset($filters['per_page']) && $filters['per_page'] === 'all') || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }

        $perPage = isset($filters['per_page']) ? min(max((int)$filters['per_page'], 1), 100) : 15;
        return $query->paginate($perPage);
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

    /**
     * Rollback and delete a payroll run with all its payslips and adjustments.
     */
    public function rollbackPayrollRun(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $run = PayrollRun::with('payslips.adjustments')->findOrFail($id);

            $payslipIds = $run->payslips->pluck('id')->toArray();

            // Check if any payslip has linked accounting salary payments
            if (!empty($payslipIds) && class_exists(\Modules\Accounting\Models\AccSalaryPayment::class)) {
                $hasPayments = \Modules\Accounting\Models\AccSalaryPayment::whereIn('payslip_id', $payslipIds)->exists();
                if ($hasPayments) {
                    throw new Exception('لا يمكن التراجع عن مسير الرواتب لوجود سندات صرف رواتب مرتبطة به في قسم المحاسبة. يرجى إلغاء سندات الصرف أولاً.', 422);
                }
            }

            // Delete adjustments and payslips
            foreach ($run->payslips as $payslip) {
                $payslip->adjustments()->delete();
                $payslip->delete();
            }

            $run->delete();

            return true;
        });
    }
}
