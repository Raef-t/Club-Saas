<?php

namespace Modules\SubscriptionManager\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // DecrementSessionCredits is also removed — session deduction now happens
        // atomically inside MemberAttendanceHandler within the same DB transaction.
        // This guarantees atomic check-in + session decrement.
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
