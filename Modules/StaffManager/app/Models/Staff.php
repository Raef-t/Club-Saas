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
        'start_date',
        'end_date',
        'contract_type',
        'work_type',
        'work_status',
        'salary_type',
        'employee_type',
        'other_tasks',
        'gym_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'base_salary' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public ?\Modules\Core\DTOs\PersonDTO $person = null;
    public ?\Modules\Core\DTOs\BranchDTO $branch = null;

    public function shifts()
    {
        return $this->hasMany(StaffShift::class);
    }

}
