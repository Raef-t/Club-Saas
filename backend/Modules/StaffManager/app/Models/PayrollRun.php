<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRun extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'branch_id',
        'period_start',
        'period_end',
        'status',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    // --- Relationships ---

    public function branch()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Branch::class, 'branch_id');
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
