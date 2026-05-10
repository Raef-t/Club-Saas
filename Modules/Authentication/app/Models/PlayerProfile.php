<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Traits\BelongsToTenant;

class PlayerProfile extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'person_id',
        'tenant_id',
        'qr_code',
        'blood_type',
        'medical_conditions',
        'emergency_contact'
    ];

    protected $casts = [
        'medical_conditions' => 'array',
        'emergency_contact' => 'array'
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
