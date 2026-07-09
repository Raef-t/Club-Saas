<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
class StaffShift extends Model
{
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
