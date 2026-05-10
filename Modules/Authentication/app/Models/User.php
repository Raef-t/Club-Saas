<?php

namespace Modules\Authentication\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Traits\BelongsToTenant;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, BelongsToTenant, HasRoles;

    protected $table = 'authentication_users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'person_id',
        'tenant_id',
        'username',
        'password',
        'is_active',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login' => 'datetime',
    ];

    /**
     * Relationship to Person
     */
    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
