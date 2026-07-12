<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;

use Modules\Authentication\Models\Person;

class Member extends Model
{
    use \Modules\Core\Traits\HasCreatedBy;

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

    public function subscriptions()
    {
        return $this->hasMany(\Modules\SubscriptionManager\Models\PlayerSubscription::class, 'member_id');
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

    public function unavailabilities()
    {
        return $this->hasMany(PlayerUnavailability::class);
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
