<?php

namespace Modules\SubscriptionManager\Events;

use Illuminate\Queue\SerializesModels;

class LockerReservationRefunded
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public object $reservation,
        public ?int $safeId = null,
        public ?float $refundAmount = null,
        public ?string $reason = null,
        public ?int $lockerId = null
    ) {}
}
