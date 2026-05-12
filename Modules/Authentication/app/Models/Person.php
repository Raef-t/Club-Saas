<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Traits\BelongsToTenant;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Person',
    title: 'Person',
    description: 'Central person entity representing players, coaches, or staff',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'full_name', type: 'string', example: 'Mohamed Ahmed'),
        new OA\Property(property: 'type', type: 'string', enum: ['player', 'coach', 'staff'], example: 'player'),
        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
        new OA\Property(property: 'dob', type: 'string', format: 'date', example: '2005-06-15'),
        new OA\Property(property: 'mobile_1', type: 'string', example: '0512345678'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'player@example.com'),
    ]
)]
class Person extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'people';

    protected $fillable = [
        'tenant_id',
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
