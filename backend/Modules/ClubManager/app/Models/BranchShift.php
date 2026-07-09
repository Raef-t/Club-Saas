<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'activity_id',
        'day_of_week',
        'start_time',
        'end_time',
        'gender_allowed',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(\Modules\Sports\Models\Activity::class);
    }
}
