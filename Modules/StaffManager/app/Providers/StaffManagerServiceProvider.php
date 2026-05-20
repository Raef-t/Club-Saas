<?php

namespace Modules\StaffManager\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Providers\RouteServiceProvider;
use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\StaffManager\Repositories\EloquentStaffRepository;

class StaffManagerServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'StaffManager';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->bind(StaffRepositoryInterface::class, EloquentStaffRepository::class);

        $this->app->bind(
            \Modules\Core\Contracts\StaffSharedServiceInterface::class,
            \Modules\StaffManager\Services\StaffSharedService::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }
}
