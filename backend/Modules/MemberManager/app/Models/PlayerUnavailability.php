<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerUnavailability extends Model
{
    use SoftDeletes;
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
