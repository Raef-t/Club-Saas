<?php

namespace Modules\NotificationManager\Models;

use Illuminate\Database\Eloquent\Model;
class NotificationLog extends Model
{
    protected $fillable = [
        'recipient_id',
        'recipient_type',
        'channel',
        'subject',
        'content',
        'status',
        'error_message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the recipient of the notification.
     */
    public function recipient()
    {
        return $this->morphTo();
    }
}
