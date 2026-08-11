<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonContact extends Model
{
    use SoftDeletes;
    protected $table = 'person_contacts';

    protected $fillable = [
        'person_id',
        'name',
        'country_code',
        'phone_number',
        'relation',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
