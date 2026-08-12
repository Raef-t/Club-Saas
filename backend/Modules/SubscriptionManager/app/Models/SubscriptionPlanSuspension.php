<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasCreatedBy;
use Modules\StaffManager\Models\Staff;

class SubscriptionPlanSuspension extends Model
{
    use HasCreatedBy, SoftDeletes;

    protected $table = 'subscription_plan_suspensions';

    protected $fillable = [
        'plan_id',
        'coach_id',
        'suspend_start_date',
        'suspend_end_date',
        'actual_end_date',
        'suspension_days',
        'reason',
        'status',
        'affected_subscribers_count',
        'notified_at',
        'created_by',
    ];

    protected $casts = [
        'suspend_start_date' => 'date:Y-m-d',
        'suspend_end_date'   => 'date:Y-m-d',
        'actual_end_date'    => 'date:Y-m-d',
        'suspension_days'    => 'integer',
        'affected_subscribers_count' => 'integer',
        'notified_at'        => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id')->withTrashed();
    }

    public function coach()
    {
        return $this->belongsTo(Staff::class, 'coach_id')->withTrashed();
    }

    public function freezes()
    {
        return $this->hasMany(SubscriptionFreeze::class, 'subscription_plan_suspension_id');
    }

    /**
     * Scope active or scheduled suspensions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }
}
