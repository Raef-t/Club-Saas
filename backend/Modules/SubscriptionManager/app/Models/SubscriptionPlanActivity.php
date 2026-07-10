<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlanActivity extends Model
{
    protected $table = 'plan_activities';

    protected $fillable = [
        'plan_id',
        'activity_id',
        'coach_id',
    ];

    protected $casts = [
        'activity_id' => 'integer',
        'coach_id' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function activity()
    {
        return $this->belongsTo(\Modules\Sports\Models\Activity::class, 'activity_id');
    }

    public function coach()
    {
        return $this->belongsTo(\Modules\StaffManager\Models\Staff::class, 'coach_id');
    }

    public function sessionTemplate()
    {
        return $this->belongsTo(\Modules\Sports\Models\SportSessionTemplate::class, 'session_template_id');
    }
}
