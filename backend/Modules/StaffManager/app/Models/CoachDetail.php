<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoachDetail extends Model
{
    use SoftDeletes;

    protected $table = 'coach_details';
    
    protected $fillable = [
        'staff_id',
        'bio',
        'experience_years',
        'working_hours_per_week',
        'gym_type',
        'work_types',
    ];

    protected $casts = [
        'experience_years'        => 'integer',
        'work_types'              => 'array',
    ];

    /**
     * The staff record this coach detail extends.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
