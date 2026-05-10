<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Traits\BelongsToTenant;
use Spatie\Translatable\HasTranslations;

class Activity extends Model
{
    use HasFactory, BelongsToTenant, HasTranslations;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'gender_allowed',
        'is_active',
    ];

    /**
     * The attributes that are translatable.
     */
    public $translatable = ['name'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
