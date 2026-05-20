<?php

namespace Modules\BranchManager\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class BranchManagerServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'BranchManager';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'branchmanager';

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
            \Modules\BranchManager\Repositories\BranchRepositoryInterface::class,
            \Modules\BranchManager\Repositories\EloquentBranchRepository::class
        );

        $this->app->bind(
            \Modules\BranchManager\Repositories\FacilityRepositoryInterface::class,
            \Modules\BranchManager\Repositories\EloquentFacilityRepository::class
        );

        $this->app->bind(
            \Modules\BranchManager\Repositories\LockerRepositoryInterface::class,
            \Modules\BranchManager\Repositories\EloquentLockerRepository::class
        );

        $this->app->bind(
            \Modules\Core\Contracts\BranchSharedServiceInterface::class,
            \Modules\BranchManager\Services\BranchSharedService::class
        );
    }
}
