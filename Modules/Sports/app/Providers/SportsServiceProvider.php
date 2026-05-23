<?php

namespace Modules\Sports\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class SportsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Sports';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'sports';

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
     * Register the service provider.
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(
            \Modules\Sports\Repositories\SessionRepositoryInterface::class,
            \Modules\Sports\Repositories\EloquentSessionRepository::class
        );

        $this->app->bind(
            \Modules\Sports\Repositories\StaffCommissionRuleRepositoryInterface::class,
            \Modules\Sports\Repositories\EloquentStaffCommissionRuleRepository::class
        );

        $this->app->bind(
            \Modules\Sports\Repositories\StaffActivityRepositoryInterface::class,
            \Modules\Sports\Repositories\EloquentStaffActivityRepository::class
        );
    }
}
