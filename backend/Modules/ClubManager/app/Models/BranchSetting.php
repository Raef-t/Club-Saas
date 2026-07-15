<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'working_hours_start',
        'working_hours_end',
        'default_club_commission_percentage',
        'default_coach_commission_percentage',
        'default_employee_salary',
        'daily_entry_price',
        'locker_price',
        'allow_freeze',
        'display_mixed_activities',
    ];

    protected $casts = [
        'working_hours_start' => 'string',
        'working_hours_end' => 'string',
        'default_club_commission_percentage' => 'decimal:2',
        'default_coach_commission_percentage' => 'decimal:2',
        'default_employee_salary' => 'decimal:2',
        'daily_entry_price' => 'decimal:2',
        'locker_price' => 'decimal:2',
        'allow_freeze' => 'boolean',
        'display_mixed_activities' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
