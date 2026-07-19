<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;

class PayslipAdjustment extends Model
{
    protected $fillable = [
        'payslip_id',
        'type', // 'bonus' or 'deduction'
        'amount',
        'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }
}
