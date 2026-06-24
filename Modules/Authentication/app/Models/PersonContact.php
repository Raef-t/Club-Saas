<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;

class PersonContact extends Model
{
    protected $table = 'person_contacts';

    protected $fillable = [
        'person_id',
        'name',
        'phone_number',
        'relation',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
