<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Member extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'person_id',
        'member_number',
        'barcode_qr_code',
        'membership_status',
        'join_date',
        'how_heard_about_us',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public ?\Modules\Core\DTOs\PersonDTO $person = null;
    public ?\Modules\Core\DTOs\BranchDTO $branch = null;

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
