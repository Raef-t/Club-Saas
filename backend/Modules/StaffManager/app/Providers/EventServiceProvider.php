<?php

namespace Modules\StaffManager\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Modules\AttendanceManager\Events\CheckInRecorded::class => [
            \Modules\StaffManager\Listeners\CalculateStaffWorkHours::class,
        ],
        \Modules\SubscriptionManager\Events\SubscriptionCreated::class => [
            \Modules\StaffManager\Listeners\RecordCoachCommission::class,
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
