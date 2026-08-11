<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonQrCode extends Model
{
    use SoftDeletes;
    protected $table = 'person_qr_codes';

    protected $fillable = [
        'person_id',
        'code',
        'day_of_week', // 0=Sunday, 1=Monday, ..., 6=Saturday
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    /**
     * Day names for human-readable output.
     */
    public const DAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    /**
     * Relationship to Person.
     */
    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /**
     * Get the day name for this code.
     */
    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? 'Unknown';
    }
}
