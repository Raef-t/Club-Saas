<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToTenant;

class MemberMeasurement extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'member_id',
        'measurement_date',
        'weight',
        'height',
        'body_fat_percentage',
        'muscle_mass',
        'waist_circumference',
    ];

    protected $casts = [
        'measurement_date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
