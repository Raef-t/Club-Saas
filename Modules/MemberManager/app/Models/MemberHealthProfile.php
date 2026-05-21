<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
class MemberHealthProfile extends Model
{
    protected $fillable = [
        'member_id',
        'allergies',
        'organic_diseases',
        'physical_injuries',
        'medications',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'sport_goal',
        'fitness_level',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
