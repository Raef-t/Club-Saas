<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes, \Modules\Core\Traits\HasCreatedBy;

    protected $table = 'staff';

    protected $fillable = [
        'person_id',
        'branch_id',
        'role',
        'employment_type',
        'base_salary',
        'is_active',
        'start_date',
        'end_date',
        'contract_type',
        'shift_type',
        'work_type',
        'work_status',
        'other_tasks',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'start_date'  => 'date',
        'end_date'    => 'date',
        'base_salary' => 'decimal:2',
    ];

    public ?\Modules\Core\DTOs\PersonDTO $person = null;
    public ?\Modules\Core\DTOs\BranchDTO $branch = null;

    // ── Relationships ───────────────────────────────────────────

    /**
     * Coach-specific details (CTI pattern: 1:1 extension).
     * Only populated when role = 'coach'.
     */
    public function coachDetail()
    {
        return $this->hasOne(CoachDetail::class);
    }

    public function shifts()
    {
        return $this->hasMany(StaffShift::class);
    }

    public function branches()
    {
        return $this->hasMany(StaffBranch::class);
    }

    public function unavailabilities()
    {
        return $this->hasMany(StaffUnavailability::class);
    }

    public function workingHours()
    {
        return $this->hasMany(StaffWorkingHour::class);
    }

    public function leaves()
    {
        return $this->hasMany(StaffLeave::class);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Check if this staff member is a coach.
     */
    public function isCoach(): bool
    {
        return $this->role === 'coach';
    }
}
