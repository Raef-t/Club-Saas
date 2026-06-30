<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLeave extends Model
{
    protected $fillable = [
        'staff_id',
        'start_date',
        'end_date',
        'reason',
        'status',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
