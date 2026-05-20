<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class CoachProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'specialization',
        'bio',
        'experience_years'
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
