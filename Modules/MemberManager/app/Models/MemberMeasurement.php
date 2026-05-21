<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
class MemberMeasurement extends Model
{
    protected $fillable = [
        'member_id',
        'measurement_date',
        'weight',
        'height',
        'body_fat_percentage',
        'muscle_mass',
        'waist_circumference',
        'activity_level',
        'bmi',
    ];

    protected $casts = [
        'measurement_date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
