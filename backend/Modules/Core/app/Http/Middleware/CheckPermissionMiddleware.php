<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    /**
     * Map of Route URI patterns & HTTP Methods to permission names.
     */
    protected array $routePermissionMap = [
        // ─── Member Stats & Dashboard ──────────────────────────────────────────
        'GET:v1/members/stats'                  => 'member.stats',
        'GET:v1/member/dashboard'               => 'member.stats',

        // ─── Members CRUD ──────────────────────────────────────────────────────
        'GET:v1/members'                        => 'member.view-any',
        'POST:v1/members/register'              => 'member.create',
        'GET:v1/members/{id}'                   => 'member.view',
        'PUT:v1/members/{id}'                   => 'member.update',
        'POST:v1/members/{id}/photo'            => 'member.update-photo',
        'DELETE:v1/members/{id}'                => 'member.delete',
        'POST:v1/members/{id}/restore'          => 'member.restore',

        // ─── Health Profiles ───────────────────────────────────────────────────
        'GET:v1/member/health-profiles'         => 'member.health-profile.view-any',
        'POST:v1/member/health-profiles'        => 'member.health-profile.create',
        'GET:v1/member/health-profiles/{id}'    => 'member.health-profile.view',
        'PUT:v1/member/health-profiles/{id}'    => 'member.health-profile.update',
        'DELETE:v1/member/health-profiles/{id}' => 'member.health-profile.delete',

        // ─── Measurements ────────────────────────────────────────────────────
        'GET:v1/member/measurements'            => 'member.measurement.view-any',
        'GET:v1/member/measurements/report'     => 'member.measurement.report',
        'POST:v1/member/measurements'           => 'member.measurement.create',
        'GET:v1/member/measurements/{id}'       => 'member.measurement.view',
        'PUT:v1/member/measurements/{id}'       => 'member.measurement.update',
        'DELETE:v1/member/measurements/{id}'    => 'member.measurement.delete',

        // ─── Unavailabilities ─────────────────────────────────────────────────
        'GET:v1/members/{member}/unavailabilities'                      => 'member.unavailability.view-any',
        'POST:v1/members/{member}/unavailabilities'                     => 'member.unavailability.create',
        'DELETE:v1/members/{member}/unavailabilities/{unavailability}' => 'member.unavailability.delete',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        // 1. Super Admin bypasses all permission checks
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // 2. If explicit permission passed to middleware (e.g. check.permission:member.create)
        if ($permission) {
            if (!$user->can($permission)) {
                return $this->forbiddenResponse($permission);
            }
            return $next($request);
        }

        // 3. Dynamic resolution based on route URI & method
        $resolvedPermission = $this->resolvePermissionFromRequest($request);

        if ($resolvedPermission) {
            if (!$user->can($resolvedPermission)) {
                return $this->forbiddenResponse($resolvedPermission);
            }
        }

        return $next($request);
    }

    /**
     * Resolve the required permission name based on current route URI and HTTP method.
     */
    protected function resolvePermissionFromRequest(Request $request): ?string
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        $method = $request->method();

        // 1. Clean URI (e.g. "api/v1/members/{id}" or "/v1/members/{id}/") -> "v1/members/{id}"
        $uri = ltrim($route->uri(), '/');
        $uri = preg_replace('#^api/#', '', $uri);
        $uri = rtrim($uri, '/');

        $key = "{$method}:{$uri}";

        // 2. Direct lookup in route map
        if (isset($this->routePermissionMap[$key])) {
            return $this->routePermissionMap[$key];
        }

        // 3. Fallback: Check with "api/" prefix just in case
        $apiKey = "{$method}:api/{$uri}";
        if (isset($this->routePermissionMap[$apiKey])) {
            return $this->routePermissionMap[$apiKey];
        }

        return null;
    }

    /**
     * Standard 403 Forbidden Response
     */
    protected function forbiddenResponse(string $permission): Response
    {
        return response()->json([
            'status'     => 'error',
            'message'    => 'عذراً، ليس لديك الصلاحية الكافية لإتمام هذا الإجراء.',
            'permission' => $permission,
        ], 403);
    }
}
