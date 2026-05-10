<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Traits\BelongsToTenant;

class CoachProfile extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'person_id',
        'tenant_id',
        'specialization',
        'bio',
        'experience_years'
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
