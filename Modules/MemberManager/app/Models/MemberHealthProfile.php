<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToTenant;

class MemberHealthProfile extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'member_id',
        'allergies',
        'organic_diseases',
        'physical_injuries',
        'medications',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
