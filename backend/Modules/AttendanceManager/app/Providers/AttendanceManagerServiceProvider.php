<?php

namespace Modules\AttendanceManager\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AttendanceManager\Handlers\MemberAttendanceHandler;
use Modules\AttendanceManager\Handlers\StaffAttendanceHandler;
use Modules\AttendanceManager\Services\UnifiedAttendanceService;

class AttendanceManagerServiceProvider extends ServiceProvider
{
    protected $moduleName = 'AttendanceManager';
    protected $moduleNameLower = 'attendancemanager';

    public function boot()
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path('attendancemanager.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower
        );

        $this->app->register(RouteServiceProvider::class);

        // Register the unified attendance service with all known handlers.
        // To add a new attendable type (e.g. Coach, Guest), simply add another
        // handler to this map — no other changes needed.
        $this->app->singleton(UnifiedAttendanceService::class, function ($app) {
            return new UnifiedAttendanceService([
                'member' => $app->make(MemberAttendanceHandler::class),
                'staff'  => $app->make(StaffAttendanceHandler::class),
                // 'coach' => $app->make(CoachAttendanceHandler::class), // future
                // 'guest' => $app->make(GuestAttendanceHandler::class), // future
            ]);
        });
    }
}
