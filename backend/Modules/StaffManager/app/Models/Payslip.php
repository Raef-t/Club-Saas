<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payslip extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'payroll_run_id',
        'staff_id',
        'staff_name',
        'base_pay',
        'commission_pay',
        'subscribers_count',
        'net_pay',
        'status',
        'paid_at',
    ];

    protected static function booted(): void
    {
        static::creating(function ($payslip) {
            if (empty($payslip->staff_name) && !empty($payslip->staff_id)) {
                $staff = Staff::with('person')->find($payslip->staff_id);
                if ($staff && $staff->person) {
                    $payslip->staff_name = $staff->person->full_name;
                }
            }
            if (empty($payslip->status)) {
                $payslip->status = 'pending';
            }
        });
    }

    protected $casts = [
        'base_pay' => 'decimal:2',
        'commission_pay' => 'decimal:2',
        'subscribers_count' => 'integer',
        'net_pay' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // --- Relationships ---

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function adjustments()
    {
        return $this->hasMany(PayslipAdjustment::class);
    }

    public function salaryPayments()
    {
        return $this->hasMany(\Modules\Accounting\Models\AccSalaryPayment::class, 'payslip_id');
    }

    public function salaryPayment()
    {
        return $this->hasOne(\Modules\Accounting\Models\AccSalaryPayment::class, 'payslip_id')->latestOfMany();
    }
}
