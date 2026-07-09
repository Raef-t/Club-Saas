<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportSession extends Model
{
    use SoftDeletes;

    protected $table = 'sports_sessions';

    protected $fillable = [
        'plan_id',
        'facility_id',
        'start_time',
        'end_time',
        'gender_allowed',
        'status',
        'booked_count',
        'template_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'booked_count' => 'integer',
    ];

    // --- Cross-module data resolved via DTOs in Service layer ---
    public ?\Modules\SubscriptionManager\Models\SubscriptionPlan $plan = null;

    /**
     * Scopes
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('start_time', $date);
    }

    public function scopeForWeek($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }
}
