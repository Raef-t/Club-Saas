<?php

namespace Modules\AttendanceManager\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AttendanceManager\Repositories\StaffAttendanceRepositoryInterface;
use Modules\AttendanceManager\Repositories\EloquentStaffAttendanceRepository;
use Modules\AttendanceManager\Repositories\MemberAttendanceRepositoryInterface;
use Modules\AttendanceManager\Repositories\EloquentMemberAttendanceRepository;
use Modules\AttendanceManager\Services\AttendanceRecorder;

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

        // Register the new Attendance Recording Engine
        $this->app->singleton(AttendanceRecorder::class);

        $this->app->bind(
            StaffAttendanceRepositoryInterface::class,
            EloquentStaffAttendanceRepository::class
        );

        $this->app->bind(
            MemberAttendanceRepositoryInterface::class,
            EloquentMemberAttendanceRepository::class
        );
    }
}
