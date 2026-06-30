<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Authentication\Models\Person;

class Member extends Model
{
    use SoftDeletes, \Modules\Core\Traits\HasCreatedBy;

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
}
