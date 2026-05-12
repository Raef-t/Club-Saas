<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\BelongsToTenant;

class SubscriptionPlan extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'duration_days',
        'session_count',
        'base_price',
        'is_active',
    ];

    protected $casts = [
        'name' => 'json',
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
    ];

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function activities()
    {
        return $this->belongsToMany(
            \Modules\Sports\Models\Activity::class,
            'plan_activities',
            'plan_id',
            'activity_id'
        )->withPivot('sessions_count', 'is_unlimited')->withTimestamps();
    }
}
