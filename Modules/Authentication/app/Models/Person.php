<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Person extends Model
{
    use HasFactory;

    protected $table = 'people';

    protected $fillable = [
        'full_name',
        'gender',
        'type',
        'dob',
        'national_id',
        'social_status',
        'address',
        'photo_url',
        'mobile_1',
        'mobile_2',
        'landline',
        'emergency_contact_name',
        'emergency_contact_phone',
        'email',
        'chronic_diseases'
    ];

    /**
     * Relationships
     */
    public function playerProfile()
    {
        return $this->hasOne(PlayerProfile::class);
    }

    public function coachProfile()
    {
        return $this->hasOne(CoachProfile::class);
    }

    public function staffProfile()
    {
        return $this->hasOne(StaffProfile::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get the active profile based on type
     */
    public function getProfileAttribute()
    {
        return match ($this->type) {
            'player' => $this->playerProfile,
            'coach' => $this->coachProfile,
            'staff', 'admin' => $this->staffProfile,
            default => null,
        };
    }
}
