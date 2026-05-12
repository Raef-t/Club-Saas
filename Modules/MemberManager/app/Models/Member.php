<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\BelongsToTenant;
use Modules\Authentication\Models\Person;
use Modules\BranchManager\Models\Branch;

class Member extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'person_id',
        'member_number',
        'barcode_qr_code',
        'membership_status',
        'join_date',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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
}
