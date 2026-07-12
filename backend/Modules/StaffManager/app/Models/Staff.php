<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;


class Staff extends Model
{
    use \Modules\Core\Traits\HasCreatedBy;

    protected $table = 'staff';

    protected $fillable = [
        'person_id',
        'role',
        'employment_type',
        'base_salary',
        'is_active',
        'start_date',
        'end_date',
        'contract_type',
        'shift_type',
        'work_status',
        'other_tasks',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'start_date'  => 'date',
        'end_date'    => 'date',
        'base_salary' => 'decimal:2',
    ];

    public ?\Modules\Core\DTOs\PersonDTO $personDto = null;
    public ?\Modules\Core\DTOs\BranchDTO $branchDto = null;

    // ── Relationships ───────────────────────────────────────────

    public function person()
    {
        return $this->belongsTo(\Modules\Authentication\Models\Person::class, 'person_id');
    }



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
        return $this->belongsToMany(\Modules\ClubManager\Models\Branch::class, 'staff_branches', 'staff_id', 'branch_id');
    }

    public function unavailabilities()
    {
        return $this->hasMany(StaffUnavailability::class);
    }


    public function leaves()
    {
        return $this->hasMany(StaffLeave::class);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Get the User associated with this staff member.
     */
    public function user()
    {
        return $this->hasOne(\Modules\Authentication\Models\User::class, 'person_id', 'person_id');
    }

    /**
     * Coach's activities.
     */
    public function activities()
    {
        return $this->belongsToMany(
            \Modules\Sports\Models\Activity::class,
            'staff_activities',
            'staff_id',
            'activity_id'
        )->withPivot('id');
    }

    /**
     * Check if this staff member is a coach.
     */
    public function isCoach(): bool
    {
        return $this->role === 'coach';
    }
}
