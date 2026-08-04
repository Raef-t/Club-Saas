<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffContract extends Model
{
    use SoftDeletes;
    protected $table = 'staff_contracts';

    protected $fillable = [
        'staff_id',
        'employment_type',
        'base_salary',
        'commission_type',
        'commission_rate',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'base_salary'     => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'start_date'      => 'date',
        'end_date'        => 'date',
        'is_active'       => 'boolean',
    ];

    /**
     * The staff member this contract belongs to.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
