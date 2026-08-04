<?php

namespace Modules\StaffManager\Listeners;

use Modules\SubscriptionManager\Events\SubscriptionCreated;
use Modules\StaffManager\Models\Staff;

class RecordCoachCommission
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  SubscriptionCreated  $event
     * @return void
     */
    public function handle(SubscriptionCreated $event)
    {
        // Commission calculations are handled dynamically on-the-fly.
    }
}
