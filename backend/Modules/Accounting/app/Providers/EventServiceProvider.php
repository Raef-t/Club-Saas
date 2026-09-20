<?php

namespace Modules\Accounting\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\SubscriptionManager\Events\SubscriptionPaymentRecorded::class => [
            \Modules\Accounting\Listeners\RecordSubscriptionPayment::class,
        ],
        \Modules\SubscriptionManager\Events\LockerReservationRefunded::class => [
            \Modules\Accounting\Listeners\RecordLockerReservationRefund::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
