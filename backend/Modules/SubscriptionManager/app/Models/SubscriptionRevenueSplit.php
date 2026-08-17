<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionRevenueSplit extends Model
{
    protected $table = 'subscription_revenue_splits';

    /**
     * هذا الجدول للبيانات التاريخية المجمدة — لا soft deletes هنا عمداً.
     * السجل المالي الأبدي لا يُحذف، فقط الاشتراك الأب يمكن soft-delete.
     */

    protected $fillable = [
        'player_subscription_id',
        'coach_id',
        'branch_id',
        'total_amount',
        'club_percentage',
        'coach_percentage',
        'club_amount',
        'coach_amount',
    ];

    protected $casts = [
        'total_amount'    => 'decimal:2',
        'club_percentage' => 'decimal:2',
        'coach_percentage'=> 'decimal:2',
        'club_amount'     => 'decimal:2',
        'coach_amount'    => 'decimal:2',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id')->withTrashed();
    }

    public function coach()
    {
        return $this->belongsTo(\Modules\StaffManager\Models\Staff::class, 'coach_id')->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Branch::class, 'branch_id');
    }
}
