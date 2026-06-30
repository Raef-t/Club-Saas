<?php

namespace Modules\FormulaEngine\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class FormulaEngineServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'FormulaEngine';
    protected string $nameLower = 'formulaengine';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
