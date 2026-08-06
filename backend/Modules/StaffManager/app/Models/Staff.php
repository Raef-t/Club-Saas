<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Staff extends Model
{
    use SoftDeletes;
    use \Modules\Core\Traits\HasCreatedBy;

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

    protected static function booted(): void
    {
        static::deleted(function ($staff) {
            if ($staff->isForceDeleting()) {
                return;
            }

            // Nullify coach_id on active sessions and subscription items to mark them as Pending Assignment (قيد التعيين)
            if (class_exists(\Modules\Sports\Models\SessionException::class)) {
                \Modules\Sports\Models\SessionException::where('coach_id', $staff->id)->update(['coach_id' => null]);
            }
            if (class_exists(\Modules\SubscriptionManager\Models\PlayerSubscriptionItem::class)) {
                \Modules\SubscriptionManager\Models\PlayerSubscriptionItem::where('coach_id', $staff->id)->update(['coach_id' => null]);
            }

            // Soft-delete linked staff details
            if ($staff->coachDetail) {
                $staff->coachDetail->delete();
            }
            $staff->contracts()->delete();
            $staff->shifts()->delete();
            $staff->leaves()->delete();
            $staff->unavailabilities()->delete();

            // Soft-delete Person & User
            if ($staff->person) {
                if ($staff->person->user) {
                    $staff->person->user->delete();
                }
                $staff->person->delete();
            }

            // Soft-delete Attendance logs
            if (class_exists(\Modules\AttendanceManager\Models\Attendance::class)) {
                \Modules\AttendanceManager\Models\Attendance::where('attendable_type', 'Modules\\StaffManager\\Models\\Staff')
                    ->where('attendable_id', $staff->id)
                    ->delete();
            }
        });

        static::restored(function ($staff) {
            $person = \Modules\Authentication\Models\Person::withTrashed()->find($staff->person_id);
            if ($person) {
                $person->restore();
                $user = \Modules\Authentication\Models\User::withTrashed()->where('person_id', $person->id)->first();
                if ($user) {
                    $user->restore();
                }
            }

            // Restore related staff details
            if ($staff->coachDetail) {
                $staff->coachDetail()->withTrashed()->restore();
            }
            $staff->contracts()->onlyTrashed()->restore();
            $staff->shifts()->onlyTrashed()->restore();
            $staff->leaves()->onlyTrashed()->restore();
            $staff->unavailabilities()->onlyTrashed()->restore();

            // Restore Locker Reservations only if the locker is still available
            if (class_exists(\Modules\ClubManager\Models\Locker::class)) {
                \Modules\SubscriptionManager\Models\LockerReservation::onlyTrashed()
                    ->where('staff_id', $staff->id)
                    ->get()
                    ->each(function ($reservation) {
                        $locker = \Modules\ClubManager\Models\Locker::find($reservation->locker_id);
                        if ($locker && $locker->status === 'available') {
                            $reservation->restore();
                            $locker->update(['status' => 'with_staff']);
                        }
                    });
            }

            // Restore Attendance logs
            if (class_exists(\Modules\AttendanceManager\Models\Attendance::class)) {
                \Modules\AttendanceManager\Models\Attendance::onlyTrashed()
                    ->where('attendable_type', 'Modules\\StaffManager\\Models\\Staff')
                    ->where('attendable_id', $staff->id)
                    ->restore();
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
}
