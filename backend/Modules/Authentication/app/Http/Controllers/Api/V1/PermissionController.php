<?php

namespace Modules\Authentication\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseController;
use Spatie\Permission\Models\Permission;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Permissions',
    description: 'عرض وإدارة صلاحيات النظام'
)]
class PermissionController extends BaseController
{
    /**
     * GET /v1/permissions
     * Get all permissions, optionally filtered by module prefix.
     *
     * Example: GET /v1/permissions?module=member
     */
    #[OA\Get(
        path: '/v1/permissions',
        summary: '📋 جلب قائمة الصلاحيات',
        description: 'يجلب جميع الصلاحيات المتاحة في النظام، مع إمكانية الفلترة حسب الموديول.',
        operationId: 'getPermissionsList',
        tags: ['Permissions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'module',
        in: 'query',
        description: 'فلترة حسب اسم الموديول (مثال: member, staff, subscription)',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'member')
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب الصلاحيات بنجاح'
    )]
    #[OA\Response(
        response: 401,
        description: '🔒 غير مصرح'
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Permission::where('guard_name', 'sanctum');

        // Filter by module prefix (e.g. ?module=member → member.*)
        if ($request->filled('module')) {
            $query->where('name', 'like', $request->input('module') . '.%');
        }

        $permissions = $query->orderBy('name')->get();

        // Group permissions by their module (first segment before the dot)
        $grouped = $permissions->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        })->map(function ($perms, $module) {
            return [
                'module'      => $module,
                'count'       => $perms->count(),
                'permissions' => $perms->map(fn($p) => [
                    'id'   => $p->id,
                    'name' => $p->name,
                ])->values(),
            ];
        })->values();

        return $this->successResponse([
            'total'   => $permissions->count(),
            'grouped' => $grouped,
            // Flat list for easy permission picking in UI
            'flat'    => $permissions->map(fn($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'module' => explode('.', $p->name)[0],
            ])->values(),
        ]);
    }
}
