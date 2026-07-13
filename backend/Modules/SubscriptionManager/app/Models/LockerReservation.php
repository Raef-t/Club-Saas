<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\StaffManager\Models\Staff;

class LockerReservation extends Model
{
    protected $fillable = [
        'locker_id',
        'member_id',
        'staff_id',
        'invoice_id',
        'start_date',
        'end_date',
        'price',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
    ];

    // Assuming member relation
    public function member()
    {
        return $this->belongsTo(\Modules\MemberManager\Models\Member::class);
    }

    // Assuming staff relation
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
