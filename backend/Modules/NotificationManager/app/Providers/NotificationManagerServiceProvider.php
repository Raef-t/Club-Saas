<?php

namespace Modules\NotificationManager\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class NotificationManagerServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'NotificationManager';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'notificationmanager';

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

    public function register(): void
    {
        parent::register();
        
        $this->app->bind(
            \Modules\NotificationManager\Repositories\NotificationTemplateRepositoryInterface::class,
            \Modules\NotificationManager\Repositories\EloquentNotificationTemplateRepository::class
        );
    }

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
