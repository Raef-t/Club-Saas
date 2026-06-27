<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;

class StaffCommissionRule extends Model
{
    protected $table = 'staff_commission_rules';

    protected $fillable = [
        'staff_id',
        'activity_id',
        'calculation_type',
        'rate_value',
        'min_players',
        'max_players',
        'is_active',
    ];

    protected $casts = [
        'rate_value'  => 'decimal:2',
        'min_players' => 'integer',
        'max_players' => 'integer',
        'is_active'   => 'boolean',
    ];

    // --- Same-module relationship only ---

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    // staff_id resolved via Core contracts — no belongsTo(Staff::class)
}
