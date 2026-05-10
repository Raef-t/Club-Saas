<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Traits\BelongsToTenant;

class Person extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'people';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'full_name',
        'gender',
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
        'chronic_diseases',
    ];

    /**
     * Relationship to User
     */
    public function user()
    {
        return $this->hasOne(User::class, 'person_id');
    }
}
