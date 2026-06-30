<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportSessionBooking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sports_session_id',
        'member_id',
        'status',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SportSession::class, 'sports_session_id');
    }
}
