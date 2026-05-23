<?php

namespace Modules\AttendanceManager\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    protected $table = 'staff_attendances';

    protected $fillable = [
        'staff_id',
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

    // Intentionally omitting belongsTo(Staff::class) to adhere to strict modular boundaries.
    // Use the Staff ID directly for referencing via DTOs/Contracts.
}
