<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlanActivity extends Model
{
    protected $table = 'plan_activities';

    protected $fillable = [
        'plan_id',
        'staff_activity_id',
    ];

    protected $casts = [
        'staff_activity_id' => 'integer',
    ];

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
}
