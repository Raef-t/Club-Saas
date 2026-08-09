<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;

class Staff extends Model
{
    use \Modules\Core\Traits\HasCreatedBy, SoftDeletes, CascadeSoftDeletes;

    protected array $cascadeDeletes = [
        'coachDetail',
        'contracts',
        'shifts',
        'commissionRules',
        'payslips',
        'attendances',
    ];

    protected $table = 'staff';

    protected $fillable = [
        'person_id',
        'role',
        'is_active',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'work_status',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'start_date'  => 'date',
        'end_date'    => 'date',
    ];

    public ?\Modules\Core\DTOs\PersonDTO $personDto = null;
    public ?\Modules\Core\DTOs\BranchDTO $branchDto = null;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($staff) {
            if ($staff->isDirty('work_status') || !isset($staff->is_active)) {
                $workStatus = $staff->work_status ?? 'active';
                $staff->work_status = $workStatus;
                $staff->is_active = ($workStatus === 'active');
            }
        });

        static::updated(function ($staff) {
            if ($staff->wasChanged('is_active') && $staff->user) {
                $staff->user->update(['is_active' => $staff->is_active]);
            }
        });
    }

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

    public function contracts()
    {
        return $this->hasMany(StaffContract::class);
    }

    public function activeContract()
    {
        return $this->hasOne(StaffContract::class)->where('is_active', true);
    }

    public function shifts()
    {
        return $this->hasMany(StaffShift::class);
    }

    public function branches()
    {
        return $this->belongsToMany(\Modules\ClubManager\Models\Branch::class, 'staff_branches', 'staff_id', 'branch_id');
    }



    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Get the first branch ID assigned to this staff member (virtual attribute).
     */
    public function getBranchIdAttribute()
    {
        $firstBranch = $this->branches()->first();
        return $firstBranch ? $firstBranch->branch_id : null;
    }

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

    public function commissionRules()
    {
        return $this->hasMany(\Modules\Sports\Models\StaffCommissionRule::class, 'staff_id');
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class, 'staff_id');
    }

    public function attendances()
    {
        return $this->morphMany(\Modules\AttendanceManager\Models\Attendance::class, 'attendable');
    }
}
