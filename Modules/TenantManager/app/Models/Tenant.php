<?php

namespace Modules\TenantManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'domain_prefix',
        'branding_config',
        'supported_languages',
        'default_language',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'branding_config' => 'array',
        'supported_languages' => 'array',
    ];
}
