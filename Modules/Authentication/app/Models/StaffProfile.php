<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Traits\BelongsToTenant;

class StaffProfile extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'person_id',
        'tenant_id',
        'job_title'
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
