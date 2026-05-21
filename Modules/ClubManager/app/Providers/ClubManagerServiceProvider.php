<?php

namespace Modules\ClubManager\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class ClubManagerServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'ClubManager';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'clubmanager';

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
     * Register the service provider.
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(
            \Modules\ClubManager\Repositories\BranchRepositoryInterface::class,
            \Modules\ClubManager\Repositories\EloquentBranchRepository::class
        );

        $this->app->bind(
            \Modules\ClubManager\Repositories\FacilityRepositoryInterface::class,
            \Modules\ClubManager\Repositories\EloquentFacilityRepository::class
        );

        $this->app->bind(
            \Modules\ClubManager\Repositories\LockerRepositoryInterface::class,
            \Modules\ClubManager\Repositories\EloquentLockerRepository::class
        );

        $this->app->bind(
            \Modules\Core\Contracts\BranchSharedServiceInterface::class,
            \Modules\ClubManager\Services\BranchSharedService::class
        );
    }
}
