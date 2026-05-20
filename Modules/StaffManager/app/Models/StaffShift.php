<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
class StaffShift extends Model
{
    protected $fillable = [
        'staff_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
