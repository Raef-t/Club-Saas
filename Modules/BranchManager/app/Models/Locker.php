<?php

namespace Modules\BranchManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Locker extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'facility_id',
        'locker_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
