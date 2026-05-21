<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class CoachProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'branch_id',
        'specialization',
        'bio',
        'experience_years',
        'work_type',
        'start_date',
        'end_date',
        'certificates',
        'payment_type',
        'commission_type',
        'commission_rate',
        'salary',
        'working_hours',
        'unavailable_times',
        'gym_type',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'unavailable_times' => 'array',
        'commission_rate' => 'decimal:2',
        'salary' => 'decimal:2',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
