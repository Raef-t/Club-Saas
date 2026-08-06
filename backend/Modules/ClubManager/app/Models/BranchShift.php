<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchShift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'facility_id',
        'name',
        'day_of_week',
        'start_time',
        'end_time',
        'gender_allowed',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

}
