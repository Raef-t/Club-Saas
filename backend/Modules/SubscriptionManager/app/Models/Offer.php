<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ClubManager\Models\Branch;

class Offer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'price',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function plans()
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'offer_subscription_plan');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'offer_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(PlayerSubscription::class, 'offer_id');
    }
}
