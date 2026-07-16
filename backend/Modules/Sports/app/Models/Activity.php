<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'branch_id',
        'activity_type_id',
        'is_private_equipment',
        'is_active',
        'session_price',
        'exercises_count',
        'estimated_calories',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the activity type for the activity.
     */
    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }

    /**
     * Get the branch that owns the activity.
     */
    public function branch()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Branch::class);
    }
}
