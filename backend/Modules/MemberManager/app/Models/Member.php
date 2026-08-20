<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Modules\Authentication\Models\Person;

class Member extends Model
{
    use \Modules\Core\Traits\HasCreatedBy, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'person_id',
        'member_number',
        'membership_status',
        'join_date',
        'how_heard_about_us',
        'reason',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saved(function ($member) {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($member->branch_id);
            }
        });

        static::deleted(function ($member) {
            if (method_exists($member, 'isForceDeleting') && $member->isForceDeleting()) {
                $member->person()->withTrashed()->first()?->forceDelete();
            } else {
                // Soft-delete linked Person & User
                if ($member->person) {
                    if ($member->person->user) {
                        $member->person->user->delete();
                    }
                    $member->person->delete();
                }

                // Soft-delete active Subscriptions & Locker Reservations
                $member->subscriptions()->get()->each(function ($sub) {
                    $sub->freezes()->delete();
                    $sub->items()->delete();
                    $sub->delete();
                });

                \Modules\SubscriptionManager\Models\LockerReservation::where('member_id', $member->id)->delete();

                // Soft-delete Health Profile & Measurements
                if ($member->healthProfile) {
                    $member->healthProfile->delete();
                }
                $member->measurements()->delete();

                // Soft-delete Attendance logs
                if (class_exists(\Modules\AttendanceManager\Models\Attendance::class)) {
                    \Modules\AttendanceManager\Models\Attendance::where('attendable_type', 'Modules\\MemberManager\\Models\\Member')
                        ->where('attendable_id', $member->id)
                        ->delete();
                }
            }

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($member->branch_id);
            }
        });

        static::restored(function ($member) {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($member->branch_id);
            }
            $person = \Modules\Authentication\Models\Person::withTrashed()->find($member->person_id);
            if ($person) {
                $person->restore();
                $user = \Modules\Authentication\Models\User::withTrashed()->where('person_id', $person->id)->first();
                if ($user) {
                    $user->restore();
                }
            }

            // Restore Health Profile & Measurements
            if ($member->healthProfile()->onlyTrashed()->exists()) {
                $member->healthProfile()->onlyTrashed()->restore();
            }
            $member->measurements()->onlyTrashed()->restore();

            // Restore Subscriptions, Freezes, Items and Locker Reservations
            $member->subscriptions()->onlyTrashed()->get()->each(function ($sub) {
                $sub->freezes()->onlyTrashed()->restore();
                $sub->items()->onlyTrashed()->restore();
                $sub->restore();
            });

            // Restore Locker Reservations only if the locker is still available
            if (class_exists(\Modules\ClubManager\Models\Locker::class)) {
                \Modules\SubscriptionManager\Models\LockerReservation::onlyTrashed()
                    ->where('member_id', $member->id)
                    ->get()
                    ->each(function ($reservation) {
                        $locker = \Modules\ClubManager\Models\Locker::find($reservation->locker_id);
                        if ($locker && $locker->status === 'available') {
                            $reservation->restore();
                            $locker->update(['status' => 'with_member']);
                        }
                    });
            }

            // Restore Attendance logs
            if (class_exists(\Modules\AttendanceManager\Models\Attendance::class)) {
                \Modules\AttendanceManager\Models\Attendance::onlyTrashed()
                    ->where('attendable_type', 'Modules\\MemberManager\\Models\\Member')
                    ->where('attendable_id', $member->id)
                    ->restore();
            }

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($member->branch_id);
            }
        });
    }



    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function branch()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Branch::class, 'branch_id');
    }

    public function healthProfile()
    {
        return $this->hasOne(MemberHealthProfile::class);
    }

    public function measurements()
    {
        return $this->hasMany(MemberMeasurement::class);
    }

    public function evaluations()
    {
        return $this->hasMany(MemberEvaluation::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(\Modules\SubscriptionManager\Models\PlayerSubscription::class, 'member_id');
    }

    public function invoices()
    {
        return $this->hasMany(\Modules\SubscriptionManager\Models\Invoice::class, 'member_id');
    }

    public function attendances()
    {
        return $this->morphMany(\Modules\AttendanceManager\Models\Attendance::class, 'attendable');
    }

    public function lockerReservations()
    {
        return $this->hasMany(\Modules\SubscriptionManager\Models\LockerReservation::class, 'member_id');
    }


    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('membership_status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('membership_status', 'inactive');
    }

    /**
     * Accessors
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->membership_status === 'active';
    }
}

