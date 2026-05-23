<?php

namespace Modules\AttendanceManager\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AttendanceManager\Repositories\StaffAttendanceRepositoryInterface;
use Modules\AttendanceManager\Repositories\EloquentStaffAttendanceRepository;
use Modules\AttendanceManager\Repositories\MemberAttendanceRepositoryInterface;
use Modules\AttendanceManager\Repositories\EloquentMemberAttendanceRepository;

class AttendanceManagerServiceProvider extends ServiceProvider
{
    protected $moduleName = 'AttendanceManager';
    protected $moduleNameLower = 'attendancemanager';

    public function boot()
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

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
