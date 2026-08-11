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
        'net_pay',
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
        });
    }

    protected $casts = [
        'base_pay' => 'decimal:2',
        'commission_pay' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    // --- Same-module relationships only ---

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
}
