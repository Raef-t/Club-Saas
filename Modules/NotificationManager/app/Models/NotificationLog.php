<?php

namespace Modules\NotificationManager\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToTenant;

class NotificationLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'recipient_id',
        'recipient_type',
        'channel',
        'subject',
        'content',
        'status',
        'error_message',
    ];

    /**
     * Get the recipient of the notification.
     */
    public function recipient()
    {
        return $this->morphTo();
    }
}
