<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;
use Modules\Authentication\Models\Person;

class Member extends Model
{
    use \Modules\Core\Traits\HasCreatedBy, SoftDeletes, CascadeSoftDeletes;

    protected array $cascadeDeletes = [
        'healthProfile',
        'measurements',
        'subscriptions',
        'evaluations',
        'invoices',
        'attendances',
        'lockerReservations',
    ];

    protected $fillable = [
        'branch_id',
        'person_id',
        'member_number',
        'membership_status',
        'join_date',
        'how_heard_about_us',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

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
     * The "booted" method of the model.
     */
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
                $member->person?->delete();
            }

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($member->branch_id);
            }
        });

        static::restored(function ($member) {
            $member->person()->onlyTrashed()->first()?->restore();

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($member->branch_id);
            }
        });
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

    public function getTotalSubscriptionsAmountAttribute()
    {
        return $this->subscriptions->sum('total_amount');
    }

    public function getTotalPaidAmountAttribute()
    {
        return $this->subscriptions->sum('paid_amount');
    }

    protected $appends = ['total_subscriptions_amount', 'total_paid_amount'];
}
