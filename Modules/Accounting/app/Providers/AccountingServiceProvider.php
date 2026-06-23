<?php

namespace Modules\Accounting\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Services\PeriodService;
use Modules\Accounting\Services\ReportService;

class AccountingServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Accounting';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'accounting';

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

        // Register core ledger and accounting singletons
        $this->app->singleton(LedgerService::class);
        $this->app->singleton(PeriodService::class);
        $this->app->singleton(ReportService::class);
    }
}
