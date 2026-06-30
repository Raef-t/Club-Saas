<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportSession extends Model
{
    use SoftDeletes;

    protected $table = 'sports_sessions';

    protected $fillable = [
        'branch_id',
        'activity_id',
        'staff_id',
        'facility_id',
        'start_time',
        'end_time',
        'max_players',
        'gender_allowed',
        'status',
        'booked_count',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'max_players' => 'integer',
        'booked_count' => 'integer',
    ];

    // --- Same-module relationships only ---

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    // --- Cross-module data resolved via DTOs in Service layer ---

    public ?\Modules\Core\DTOs\BranchDTO $branch = null;
    public ?\Modules\Core\DTOs\StaffDTO $staff = null;

    /**
     * Scopes
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('start_time', $date);
    }

    public function scopeForWeek($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }
}
