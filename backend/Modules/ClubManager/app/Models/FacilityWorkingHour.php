<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityWorkingHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'day_of_week',
        'open_time',
        'close_time',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
