<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToTenant;

class StaffAttendance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'check_in',
        'check_out',
        'total_hours',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
