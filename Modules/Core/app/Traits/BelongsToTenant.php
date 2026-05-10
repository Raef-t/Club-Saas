<?php

namespace Modules\Core\app\Traits;

use Modules\Core\app\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (session()->has('tenant_id')) {
                $model->tenant_id = session()->get('tenant_id');
            }
        });
    }

    /**
     * Relationship to Tenant
     */
    public function tenant()
    {
        // Assuming Tenant model is in TenantManager module
        return $this->belongsTo(\Modules\TenantManager\app\Models\Tenant::class, 'tenant_id');
    }
}
