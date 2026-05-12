<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToTenant;

class StaffShift extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
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
