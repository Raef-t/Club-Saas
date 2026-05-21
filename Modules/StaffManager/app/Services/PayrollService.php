<?php

namespace Modules\StaffManager\Services;

use Modules\StaffManager\Models\PayrollRun;
use Modules\StaffManager\Models\Payslip;
use Modules\StaffManager\Models\Staff;
use Illuminate\Support\Facades\DB;
use Exception;

class PayrollService
{
    /**
     * Create a new payroll run.
     */
    public function createPayrollRun(string $periodStart, string $periodEnd): PayrollRun
    {
        return PayrollRun::create([
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'draft',
        ]);
    }

    /**
     * Get all payroll runs.
     */
    public function getAllPayrollRuns()
    {
        return PayrollRun::with('payslips')->latest('period_start')->get();
    }

    /**
     * Get a single payroll run with payslips.
     */
    public function getPayrollRunById(int $id): PayrollRun
    {
        return PayrollRun::with(['payslips.staff'])->findOrFail($id);
    }

    /**
     * Generate payslips for all active staff in a payroll run.
     * Calculates base_pay from staff salary and commission_pay from commission rules.
     */
    public function generatePayslips(int $payrollRunId): PayrollRun
    {
        $payrollRun = PayrollRun::findOrFail($payrollRunId);

        if ($payrollRun->status !== 'draft') {
            throw new Exception(__('Payslips can only be generated for draft payroll runs.'));
        }

        return DB::transaction(function () use ($payrollRun) {
            // Remove any existing payslips for this run
            $payrollRun->payslips()->delete();

            // Get all active staff
            $staffMembers = Staff::where('is_active', true)->get();

            foreach ($staffMembers as $staff) {
                $basePay = $this->calculateBasePay($staff);
                $commissionPay = $this->calculateCommissionPay($staff, $payrollRun);
                $netPay = $basePay + $commissionPay;

                Payslip::create([
                    'payroll_run_id' => $payrollRun->id,
                    'staff_id' => $staff->id,
                    'base_pay' => $basePay,
                    'commission_pay' => $commissionPay,
                    'net_pay' => $netPay,
                ]);
            }

            return $payrollRun->load('payslips');
        });
    }

    /**
     * Approve a payroll run (changes status from draft to approved).
     */
    public function approvePayrollRun(int $payrollRunId): PayrollRun
    {
        $payrollRun = PayrollRun::findOrFail($payrollRunId);

        if ($payrollRun->status !== 'draft') {
            throw new Exception(__('Only draft payroll runs can be approved.'));
        }

        if ($payrollRun->payslips()->count() === 0) {
            throw new Exception(__('Cannot approve a payroll run without payslips. Generate payslips first.'));
        }

        $payrollRun->update(['status' => 'approved']);
        return $payrollRun->load('payslips');
    }

    /**
     * Calculate base pay based on staff employment type.
     */
    protected function calculateBasePay(Staff $staff): float
    {
        if ($staff->employment_type === 'commission_based') {
            return 0;
        }

        return (float) $staff->base_salary;
    }

    /**
     * Calculate commission pay based on staff commission rules.
     * Commission is calculated from the staff_commission_rules table
     * and the sessions they conducted during the payroll period.
     */
    protected function calculateCommissionPay(Staff $staff, PayrollRun $payrollRun): float
    {
        if ($staff->employment_type === 'fixed_salary') {
            return 0;
        }

        // Count sessions this staff conducted during the payroll period
        // Using direct DB query since sports_sessions is accessible via schema
        $sessionsCount = DB::table('sports_sessions')
            ->where('staff_id', $staff->id)
            ->where('status', 'completed')
            ->whereBetween('start_time', [$payrollRun->period_start, $payrollRun->period_end])
            ->count();

        // Apply the staff's commission rate to the session count
        // This is a simplified calculation — more complex logic can be added per activity
        return $sessionsCount * (float) $staff->commission_rate;
    }
}
