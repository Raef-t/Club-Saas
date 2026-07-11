<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlanActivity extends Model
{
    protected $table = 'plan_activities';

    protected $fillable = [
        'plan_id',
        'staff_activity_id',
        'session_template_id',
    ];

    protected $casts = [
        'staff_activity_id' => 'integer',
        'plan_id' => 'integer',
    ];

    protected $appends = ['activity_id', 'coach_id'];

    protected $with = ['staffActivity'];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function staffActivity()
    {
        return $this->belongsTo(\Modules\Sports\Models\StaffActivity::class, 'staff_activity_id');
    }

    public function sessionTemplate()
    {
        return $this->belongsTo(\Modules\Sports\Models\SportSessionTemplate::class, 'session_template_id');
    }

    public function getActivityIdAttribute()
    {
        return $this->staffActivity ? $this->staffActivity->activity_id : null;
    }

    public function getCoachIdAttribute()
    {
        return $this->staffActivity ? $this->staffActivity->staff_id : null;
    }

    public function activity()
    {
        return $this->hasOneThrough(
            \Modules\Sports\Models\Activity::class,
            \Modules\Sports\Models\StaffActivity::class,
            'id', // Foreign key on the environments table...
            'id', // Foreign key on the deployments table...
            'staff_activity_id', // Local key on the projects table...
            'activity_id' // Local key on the environments table...
        );
    }

    public function coach()
    {
        return $this->hasOneThrough(
            \Modules\StaffManager\Models\Staff::class,
            \Modules\Sports\Models\StaffActivity::class,
            'id', 
            'id', 
            'staff_activity_id', 
            'staff_id' 
        );
    }
}
