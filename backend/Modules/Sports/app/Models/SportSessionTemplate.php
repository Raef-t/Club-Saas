<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportSessionTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'sport_session_templates';

    protected $fillable = [
        'plan_id',
        'facility_id',
        'day_of_week',
        'start_time',
        'end_time',
        'max_players',
        'gender_allowed',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'max_players' => 'integer',
        'is_active' => 'boolean',
    ];

    // --- Cross-module data resolved via DTOs in Service layer ---
    public ?\Modules\SubscriptionManager\Models\SubscriptionPlan $plan = null;

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
