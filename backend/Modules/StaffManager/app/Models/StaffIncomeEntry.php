<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;

class StaffIncomeEntry extends Model
{
    protected $table = 'staff_income_entries';

    protected $fillable = [
        'staff_id',
        'type',
        'source_type',
        'source_id',
        'base_amount',
        'percentage_applied',
        'amount',
        'status',
        'description',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'percentage_applied' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the staff member associated with this entry.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Get the source of this entry (e.g., PlanSubscription)
     */
    public function source()
    {
        return $this->morphTo();
    }
}
