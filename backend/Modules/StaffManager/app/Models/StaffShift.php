<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffShift extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'staff_id',
        'branch_shift_id',
    ];

    public function branchShift()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\BranchShift::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
