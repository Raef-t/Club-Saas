<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PlayerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
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
