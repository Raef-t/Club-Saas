<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraService extends Model
{
    protected $table = 'extra_services';

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'default_price',
        'is_active',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the branch that owns the extra service.
     */
    public function branch()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Branch::class);
    }
}
