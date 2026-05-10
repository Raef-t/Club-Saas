<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\TenantManager\Models\Tenant;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (!$tenantId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant ID is required (X-Tenant-ID header missing)'
            ], 403);
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant || $tenant->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or inactive Tenant'
            ], 403);
        }

        // Store tenant info in session or config for global access
        session()->put('tenant_id', $tenant->id);
        session()->put('tenant_name', $tenant->name);
        
        // Optional: Set app locale based on tenant default if not specified
        if (!$request->header('X-Locale')) {
            app()->setLocale($tenant->default_language);
        }

        return $next($request);
    }
}
