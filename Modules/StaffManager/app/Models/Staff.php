<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Staff extends Model
{
    use SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'person_id',
        'branch_id',
        'role',
        'employment_type',
        'base_salary',
        'commission_rate',
        'specialization',
        'is_active',
    ];

    public ?\Modules\Core\DTOs\PersonDTO $person = null;
    public ?\Modules\Core\DTOs\BranchDTO $branch = null;

    public function shifts()
    {
        return $this->hasMany(StaffShift::class);
    }

    public function attendances()
    {
        return $this->hasMany(StaffAttendance::class);
    }
}
