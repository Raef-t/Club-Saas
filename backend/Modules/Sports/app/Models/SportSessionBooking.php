<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class SportSessionBooking extends Model
{
    use SoftDeletes;

    protected $table = 'sports_session_bookings';

    protected $fillable = [
        'sports_session_id',
        'member_id',
        'status',
    ];

    public function member()
    {
        return $this->belongsTo(\Modules\MemberManager\Models\Member::class, 'member_id');
    }
}
