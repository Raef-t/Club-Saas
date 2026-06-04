<?php

namespace Modules\NotificationManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\NotificationManager\Models\NotificationLog;

class NotificationLogged
{
    use Dispatchable, SerializesModels;

    public NotificationLog $log;

    /**
     * Create a new event instance.
     *
     * @param NotificationLog $log
     */
    public function __construct(NotificationLog $log)
    {
        $this->log = $log;
    }
}
