<?php

namespace Modules\SubscriptionManager\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Modules\AttendanceManager\Events\CheckInRecorded::class => [
            \Modules\SubscriptionManager\Listeners\DecrementSessionCredits::class,
            \Modules\SubscriptionManager\Listeners\DeductSessionOnCheckIn::class,
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
