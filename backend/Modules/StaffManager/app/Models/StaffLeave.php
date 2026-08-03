<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffLeave extends Model
{
    use SoftDeletes;
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
