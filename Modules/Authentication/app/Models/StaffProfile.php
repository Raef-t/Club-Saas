<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class StaffProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'job_title'
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
