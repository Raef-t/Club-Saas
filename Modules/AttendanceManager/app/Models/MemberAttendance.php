<?php

namespace Modules\AttendanceManager\Models;

use Illuminate\Database\Eloquent\Model;

class MemberAttendance extends Model
{
    protected $table = 'member_attendances';

    protected $fillable = [
        'member_id',
        'facility_id',
        'check_in',
        'check_out',
        'status',
        'notes'
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    // Intentionally omitting belongsTo(Member::class) to adhere to strict modular boundaries.
}
