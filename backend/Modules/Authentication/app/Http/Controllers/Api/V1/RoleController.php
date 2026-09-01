<?php

namespace Modules\Authentication\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Modules\Authentication\Http\Requests\StoreRoleRequest;
use Modules\Authentication\Http\Requests\UpdateRoleRequest;
use Modules\Authentication\Http\Requests\SyncRolePermissionsRequest;
use Modules\Core\Http\Controllers\Api\BaseController;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Roles',
    description: 'إدارة الأدوار (Roles) وتعيين الصلاحيات لها'
)]
class RoleController extends BaseController
{
    /** Roles that cannot be deleted */
    private const PROTECTED_ROLES = ['super_admin', 'admin'];

    /**
     * GET /v1/roles
     * List all roles with their assigned permissions.
     */
    #[OA\Get(
        path: '/v1/roles',
        summary: '📋 جلب قائمة الأدوار',
        description: 'يجلب جميع الأدوار المتاحة في النظام مع صلاحياتها.',
        operationId: 'getRolesList',
        tags: ['Roles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ تم جلب الأدوار بنجاح')]
    #[OA\Response(response: 401, description: '🔒 غير مصرح')]
    public function index(): JsonResponse
    {
        $roles = Role::where('guard_name', 'sanctum')
            ->with('permissions')
            ->get()
            ->map(fn($role) => [
                'id'                 => $role->id,
                'name'               => $role->name,
                'permissions_count'  => $role->permissions->count(),
                'permissions'        => $role->permissions
                    ->sortBy('name')
                    ->map(fn($p) => [
                        'id'     => $p->id,
                        'name'   => $p->name,
                        'module' => explode('.', $p->name)[0],
                    ])->values(),
                'is_protected'       => in_array($role->name, self::PROTECTED_ROLES),
            ]);

        return $this->successResponse(['roles' => $roles, 'total' => $roles->count()]);
    }

    /**
     * POST /v1/roles
     * Create a new role.
     */
    #[OA\Post(
        path: '/v1/roles',
        summary: '➕ إنشاء دور جديد',
        description: 'ينشئ دوراً جديداً في النظام.',
        operationId: 'createRole',
        tags: ['Roles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'مدير الصالة',
                    description: 'اسم الدور'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: '✅ تم إنشاء الدور بنجاح')]
    #[OA\Response(response: 422, description: '❌ خطأ في البيانات')]
    public function store(StoreRoleRequest $request): JsonResponse
    {
        // Create for sanctum guard (API)
        $role = Role::firstOrCreate([
            'name'       => $request->name,
            'guard_name' => 'sanctum',
        ]);

        // Also create for web guard to avoid issues
        Role::firstOrCreate([
            'name'       => $request->name,
            'guard_name' => 'web',
        ]);

        return $this->successResponse([
            'role' => [
                'id'                => $role->id,
                'name'              => $role->name,
                'permissions_count' => 0,
                'permissions'       => [],
                'is_protected'      => false,
            ],
        ], 'تم إنشاء الدور بنجاح', 201);
    }

    /**
     * GET /v1/roles/{id}
     * Show role details with all its permissions.
     */
    #[OA\Get(
        path: '/v1/roles/{id}',
        summary: '🔍 عرض تفاصيل دور محدد',
        operationId: 'getRoleDetail',
        tags: ['Roles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3))]
    #[OA\Response(response: 200, description: '✅ تم جلب تفاصيل الدور بنجاح')]
    #[OA\Response(response: 404, description: '❌ الدور غير موجود')]
    public function show(int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'sanctum')
            ->with('permissions')
            ->findOrFail($id);

        return $this->successResponse([
            'role' => [
                'id'                => $role->id,
                'name'              => $role->name,
                'permissions_count' => $role->permissions->count(),
                'permissions'       => $role->permissions
                    ->sortBy('name')
                    ->map(fn($p) => [
                        'id'     => $p->id,
                        'name'   => $p->name,
                        'module' => explode('.', $p->name)[0],
                    ])->values(),
                'is_protected'      => in_array($role->name, self::PROTECTED_ROLES),
            ],
        ]);
    }

    /**
     * PUT /v1/roles/{id}
     * Update role name (protected roles cannot be renamed).
     */
    #[OA\Put(
        path: '/v1/roles/{id}',
        summary: '✏️ تعديل اسم الدور',
        description: 'يعدل اسم دور موجود في النظام. لا يمكن تعديل الأدوار المحمية (super_admin, admin).',
        operationId: 'updateRole',
        tags: ['Roles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'مشرف الصالة الجديد', description: 'الاسم الجديد للدور'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم تعديل اسم الدور بنجاح')]
    #[OA\Response(response: 403, description: '🚫 لا يمكن تعديل دور محمي')]
    #[OA\Response(response: 404, description: '❌ الدور غير موجود')]
    #[OA\Response(response: 422, description: '❌ خطأ في البيانات')]
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'sanctum')->findOrFail($id);

        if (in_array($role->name, self::PROTECTED_ROLES)) {
            return $this->errorResponse(
                'لا يمكن تعديل اسم الدور "' . $role->name . '" لأنه دور محمي في النظام.',
                403
            );
        }

        $oldName = $role->name;
        $role->name = $request->name;
        $role->save();

        // Also update for web guard if it exists
        Role::where('name', $oldName)->where('guard_name', 'web')->update([
            'name' => $request->name,
        ]);

        return $this->successResponse([
            'role' => [
                'id'                => $role->id,
                'name'              => $role->name,
                'permissions_count' => $role->permissions()->count(),
                'is_protected'      => false,
            ],
        ], 'تم تعديل اسم الدور بنجاح');
    }

    /**
     * PUT /v1/roles/{id}/permissions
     * Sync (replace) permissions assigned to a role.
     * Sends the full list of desired permissions — previous ones are removed.
     */
    #[OA\Put(
        path: '/v1/roles/{id}/permissions',
        summary: '🔄 تعيين صلاحيات لدور (Sync)',
        description: 'يستبدل جميع صلاحيات الدور بالقائمة الجديدة المرسلة (sync).',
        operationId: 'syncRolePermissions',
        tags: ['Roles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['permissions'],
            properties: [
                new OA\Property(
                    property: 'permissions',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['member.view-any', 'member.create', 'member.update']
                ),
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم تحديث صلاحيات الدور بنجاح')]
    #[OA\Response(response: 404, description: '❌ الدور غير موجود')]
    #[OA\Response(response: 422, description: '❌ صلاحية غير موجودة في النظام')]
    public function syncPermissions(SyncRolePermissionsRequest $request, int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'sanctum')->findOrFail($id);

        // Fetch only sanctum-guard permissions that match the requested names
        $permissions = Permission::where('guard_name', 'sanctum')
            ->whereIn('name', $request->permissions)
            ->get();

        $role->syncPermissions($permissions);

        $fresh = $role->fresh()->permissions;

        return $this->successResponse([
            'role'               => $role->name,
            'permissions_count'  => $fresh->count(),
            'permissions'        => $fresh->sortBy('name')->map(fn($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'module' => explode('.', $p->name)[0],
            ])->values(),
        ], 'تم تحديث صلاحيات الدور بنجاح');
    }

    /**
     * DELETE /v1/roles/{id}
     * Delete a role (protected roles cannot be deleted).
     */
    #[OA\Delete(
        path: '/v1/roles/{id}',
        summary: '🗑️ حذف دور',
        description: 'يحذف دوراً من النظام. لا يمكن حذف الأدوار المحمية (super_admin, admin).',
        operationId: 'deleteRole',
        tags: ['Roles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3))]
    #[OA\Response(response: 200, description: '✅ تم حذف الدور بنجاح')]
    #[OA\Response(response: 403, description: '🚫 لا يمكن حذف دور محمي')]
    #[OA\Response(response: 404, description: '❌ الدور غير موجود')]
    public function destroy(int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'sanctum')->findOrFail($id);

        if (in_array($role->name, self::PROTECTED_ROLES)) {
            return $this->errorResponse(
                'لا يمكن حذف الدور "' . $role->name . '" لأنه دور محمي في النظام.',
                403
            );
        }

        $roleName = $role->name;

        // Remove from both guards
        $role->delete();
        Role::where('name', $roleName)->where('guard_name', 'web')->delete();

        return $this->successResponse(null, 'تم حذف الدور "' . $roleName . '" بنجاح.');
    }
}


