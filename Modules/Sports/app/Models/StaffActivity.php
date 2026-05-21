<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;

class StaffActivity extends Model
{
    protected $table = 'staff_activities';

    protected $fillable = [
        'staff_id',
        'activity_id',
    ];

    // --- Same-module relationship only ---

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    // staff_id resolved via Core contracts — no belongsTo(Staff::class)
}
