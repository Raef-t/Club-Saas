<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'staff_id',
        'base_pay',
        'commission_pay',
        'net_pay',
    ];

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
}
