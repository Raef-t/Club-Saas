<?php

namespace Modules\SubscriptionManager\Events;

use Illuminate\Queue\SerializesModels;
use Modules\SubscriptionManager\Models\Payment;

class SubscriptionPaymentRecorded
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Payment $payment) {}
}
