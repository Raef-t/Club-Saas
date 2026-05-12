<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\BelongsToTenant;
use Modules\Authentication\Models\Person;
use Modules\BranchManager\Models\Branch;

class Staff extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $table = 'staff';

    protected $fillable = [
        'tenant_id',
        'person_id',
        'branch_id',
        'role',
        'employment_type',
        'base_salary',
        'commission_rate',
        'specialization',
        'is_active',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function shifts()
    {
        return $this->hasMany(StaffShift::class);
    }

    public function attendances()
    {
        return $this->hasMany(StaffAttendance::class);
    }
}
