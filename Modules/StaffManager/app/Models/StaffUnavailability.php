<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;

class StaffUnavailability extends Model
{
    protected $table = 'staff_unavailabilities';

    protected $fillable = [
        'staff_id',
        'start_datetime',
        'end_datetime',
        'reason',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
