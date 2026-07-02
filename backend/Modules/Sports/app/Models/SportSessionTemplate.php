<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportSessionTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'sport_session_templates';

    protected $fillable = [
        'branch_id',
        'activity_id',
        'staff_id',
        'facility_id',
        'day_of_week',
        'start_time',
        'end_time',
        'max_players',
        'gender_allowed',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'max_players' => 'integer',
        'is_active' => 'boolean',
    ];

    // --- Same-module relationships ---

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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
