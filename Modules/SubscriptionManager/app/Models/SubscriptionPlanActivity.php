<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlanActivity extends Model
{
    protected $table = 'plan_activities';

    protected $fillable = [
        'plan_id',
        'activity_id',
        'sessions_count',
        'is_unlimited',
    ];

    protected $casts = [
        'is_unlimited' => 'boolean',
        'sessions_count' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}
