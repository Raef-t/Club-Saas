<?php

namespace Modules\SubscriptionManager\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\SubscriptionManager\Models\PlayerSubscription; // Or whatever the subscription model is, maybe just pass the model itself. Wait, what is the model name for a subscription?

// Wait, the repository creates a subscription. I should check the class name.
class SubscriptionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subscription;
    public $plan;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($subscription, $plan)
    {
        $this->subscription = $subscription;
        $this->plan = $plan;
    }
}
