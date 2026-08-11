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
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::deleted(function ($template) {
            $template->exceptions()->delete();
        });
    }

    // --- Cross-module data resolved via DTOs in Service layer ---
    public ?\Modules\SubscriptionManager\Models\SubscriptionPlan $plan = null;

    public function exceptions()
    {
        return $this->hasMany(SessionException::class, 'sport_session_template_id');
    }


    /**
     * Scopes
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(\Modules\SubscriptionManager\Models\SubscriptionPlan::class, 'plan_id');
    }

    public function facility()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Facility::class, 'facility_id');
    }
}
