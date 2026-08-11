<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberHealthProfile extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'member_id',
        'allergies',
        'organic_diseases',
        'physical_injuries',
        'medications',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_country_code',
        'emergency_contact_phone',
        'sport_goal',
        'fitness_level',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
