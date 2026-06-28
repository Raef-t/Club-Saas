<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;

class CoachDetail extends Model
{
    protected $table = 'coach_details';

    protected $fillable = [
        'staff_id',
        'specialization',
        'bio',
        'experience_years',
        'payment_type',
        'commission_type',
        'default_commission_rate',
        'working_hours_per_week',
        'gym_type',
    ];

    protected $casts = [
        'experience_years'        => 'integer',
        'default_commission_rate' => 'decimal:2',
        'working_hours_per_week'  => 'decimal:2',
    ];

    /**
     * The staff record this coach detail extends.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Coach's certifications.
     */
    public function certifications()
    {
        return $this->hasMany(CoachCertification::class);
    }

}
