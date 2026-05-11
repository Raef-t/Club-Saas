<?php

namespace Modules\BranchManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\BelongsToTenant;

class Facility extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'capacity',
        'gender_restriction',
        'is_active',
    ];

    protected $casts = [
        'name' => 'json',
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    /**
     * Relationship: A facility belongs to a branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
