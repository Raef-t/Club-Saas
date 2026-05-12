<?php

namespace Modules\SubscriptionManager\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class SubscriptionManagerServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'SubscriptionManager';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'subscriptionmanager';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    public function register(): void
    {
        parent::register();

        $this->app->bind(
            \Modules\SubscriptionManager\Repositories\SubscriptionPlanRepositoryInterface::class,
            \Modules\SubscriptionManager\Repositories\EloquentSubscriptionPlanRepository::class
        );

        $this->app->bind(
            \Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface::class,
            \Modules\SubscriptionManager\Repositories\EloquentPlayerSubscriptionRepository::class
        );
    }
}
