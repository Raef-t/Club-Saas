<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchHoliday extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'type', // 'weekly' or 'specific_dates'
        'day_of_week', // 0-6 (Sunday-Saturday) for weekly holidays
        'start_date', // for specific_dates
        'end_date', // for specific_dates
        'reason', // for specific_dates
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
