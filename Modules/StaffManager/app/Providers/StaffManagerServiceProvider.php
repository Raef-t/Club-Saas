<?php

namespace Modules\StaffManager\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\StaffManager\Repositories\EloquentStaffRepository;

class StaffManagerServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'StaffManager';
    protected string $nameLower = 'staffmanager';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();
        
        $this->app->bind(StaffRepositoryInterface::class, EloquentStaffRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }
}
