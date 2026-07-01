<?php

namespace Modules\WalletManager\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class WalletManagerServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'WalletManager';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'walletmanager';

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
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    public function register(): void
    {
        parent::register();

        $this->app->bind(
            \Modules\WalletManager\Repositories\WalletRepositoryInterface::class,
            \Modules\WalletManager\Repositories\EloquentWalletRepository::class
        );

        $this->app->bind(
            \Modules\WalletManager\Repositories\WalletTransactionRepositoryInterface::class,
            \Modules\WalletManager\Repositories\EloquentWalletTransactionRepository::class
        );
    }
}

