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
        'age',
        'dob',
        'national_id',
        'social_status',
        'address',
        'photo_url',
        'email',
        'chronic_diseases',
        'children_count',
        'how_did_you_hear',
        'notes',
    ];

    /**
     * Relationships
     */

    public function contacts()
    {
        return $this->hasMany(PersonContact::class, 'person_id');
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get the active profile based on type.
     * Coach/staff profiles are now managed by StaffManager module via DTOs.
     */
    public function getProfileAttribute()
    {
        return match ($this->type) {
            'player' => null,
            'coach', 'staff', 'admin' => null, // Resolved via StaffManager DTOs
            default => null,
        };
    }

    /**
     * Prepare photo_url for frontend by prepending storage/
     */
    public function getPhotoUrlAttribute($value)
    {
        if ($value && !str_starts_with($value, 'http') && !str_starts_with($value, 'storage/')) {
            return 'storage/' . $value;
        }

        return $value;
    }

    public function wallet()
    {
        return $this->hasOne(\Modules\WalletManager\Models\Wallet::class, 'person_id');
    }
}
