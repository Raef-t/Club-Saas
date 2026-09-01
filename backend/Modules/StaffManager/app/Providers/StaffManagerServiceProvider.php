<?php

namespace Modules\StaffManager\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\StaffManager\Providers\RouteServiceProvider;
use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\StaffManager\Repositories\EloquentStaffRepository;

class StaffManagerServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'StaffManager';

    protected array $commands = [];

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->commands($this->commands);

        $this->app->bind(StaffRepositoryInterface::class, EloquentStaffRepository::class);

        $this->app->bind(
            \Modules\Core\Contracts\StaffSharedServiceInterface::class,
            \Modules\StaffManager\Services\StaffSharedService::class
        );

        $this->app->bind(
            \Modules\StaffManager\Repositories\PayslipRepositoryInterface::class,
            \Modules\StaffManager\Repositories\EloquentPayslipRepository::class
        );

        $this->app->bind(
            \Modules\StaffManager\Repositories\StaffShiftRepositoryInterface::class,
            \Modules\StaffManager\Repositories\EloquentStaffShiftRepository::class
        );

        // Bind the staff attendance policy to the service container
        $this->app->singleton(
            'attendance.policy.staff',
            \Modules\StaffManager\Policies\StaffAttendancePolicy::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        // Register the polymorphic alias in Eloquent morphMap
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'staff' => \Modules\StaffManager\Models\Staff::class,
        ]);
    }
}
