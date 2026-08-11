<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberMeasurement extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'member_id',
        'measurement_date',
        'weight',
        'height',
        'body_fat_percentage',
        'muscle_mass',
        'waist_circumference',
        'neck_circumference',
        'shoulder_circumference',
        'right_bicep',
        'left_bicep',
        'hip_circumference',
        'chest_circumference',
        'right_thigh_mid',
        'left_thigh',
        'right_calf',
        'left_calf',
        'fat_free_mass_percentage',
        'bmi',
        'body_water_percentage',
        'resting_metabolic_rate',
        'total_daily_energy_expenditure',
        'physical_activity_level',
        'buttocks_circumference',
        'above_right_knee',
        'above_left_knee',
    ];

    protected $casts = [
        'measurement_date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
