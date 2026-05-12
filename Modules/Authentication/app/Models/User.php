<?php

namespace Modules\Authentication\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Traits\BelongsToTenant;
use Spatie\Permission\Traits\HasRoles;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    title: 'User',
    description: 'User authentication account',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'username', type: 'string', example: 'admin'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'person', ref: '#/components/schemas/Person'),
    ]
)]
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
