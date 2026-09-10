<?php

namespace Modules\AppVersions\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class AppVersionsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'AppVersions';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'appversions';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        parent::register();
    }
}
