<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerUnavailability extends Model
{
    protected $table = 'player_unavailabilities';

    protected $fillable = [
        'member_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
