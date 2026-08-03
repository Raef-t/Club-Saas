<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;

class ActivityType extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;

    protected array $cascadeDeletes = ['activities'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'is_active',
        'is_session_based',
        'has_unlimited_subscribers',
        'has_shifts',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_session_based' => 'boolean',
        'has_unlimited_subscribers' => 'boolean',
        'has_shifts' => 'boolean',
    ];

    /**
     * Get the activities for the activity type.
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
