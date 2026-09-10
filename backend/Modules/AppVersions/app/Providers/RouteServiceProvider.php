<?php

namespace Modules\AppVersions\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'AppVersions';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        $webRoutePath = module_path($this->name, '/routes/web.php');
        if (file_exists($webRoutePath)) {
            Route::middleware('web')->group($webRoutePath);
        }
    }

    protected function mapApiRoutes(): void
    {
        $apiRoutePath = module_path($this->name, '/routes/api.php');
        if (file_exists($apiRoutePath)) {
            Route::middleware('api')->prefix('api')->name('api.')->group($apiRoutePath);
        }
    }
}
