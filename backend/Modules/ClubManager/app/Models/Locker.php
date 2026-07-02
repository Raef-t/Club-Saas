<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Locker extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'locker_number',
        'status',
        // Tracks which open attendance record currently holds this locker key.
        // NULL = available (no one is holding this key).
        'current_attendance_id',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}