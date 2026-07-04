<?php

namespace Modules\NotificationManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\NotificationManager\Models\Notification;

class NotificationCreated
{
    use Dispatchable, SerializesModels;

    public Notification $notification;

    /**
     * نمرر الإشعار فقط بدون أي منطق
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }
}
