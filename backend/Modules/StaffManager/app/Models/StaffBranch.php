<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;

class StaffBranch extends Model
{
    protected $fillable = [
        'staff_id',
        'branch_id',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    // Zero cross-coupling: We don't define belongsTo(Branch::class) directly.
    // Use DTOs or simple IDs instead.
    public ?\Modules\Core\DTOs\BranchDTO $branch = null;
}
