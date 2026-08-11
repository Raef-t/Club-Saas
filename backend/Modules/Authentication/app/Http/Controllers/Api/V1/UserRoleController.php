<?php

namespace Modules\Authentication\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authentication\Models\User;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'User Roles',
    description: 'تعيين وإدارة أدوار المستخدمين'
)]
class UserRoleController extends BaseController
{
    /**
     * GET /v1/users/{userId}/roles
     * Get all roles and permissions assigned to a specific user.
     */
    #[OA\Get(
        path: '/v1/users/{userId}/roles',
        summary: '👤 جلب أدوار وصلاحيات مستخدم محدد',
        description: 'يجلب الأدوار والصلاحيات المعينة لمستخدم معين.',
        operationId: 'getUserRoles',
        tags: ['User Roles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم جلب بيانات المستخدم بنجاح')]
    #[OA\Response(response: 404, description: '❌ المستخدم غير موجود')]
    public function index(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        return $this->successResponse([
            'user_id'     => $user->id,
            'username'    => $user->username,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()
                ->sortBy('name')
                ->map(fn($p) => [
                    'id'     => $p->id,
                    'name'   => $p->name,
                    'module' => explode('.', $p->name)[0],
                ])->values(),
        ]);
    }

    /**
     * POST /v1/users/{userId}/roles
     * Assign a role to a user.
     */
    #[OA\Post(
        path: '/v1/users/{userId}/roles',
        summary: '➕ تعيين دور لمستخدم',
        description: 'يعين دوراً لمستخدم معين.',
        operationId: 'assignRoleToUser',
        tags: ['User Roles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['role'],
            properties: [
                new OA\Property(property: 'role', type: 'string', example: 'member_manager',
                    description: 'اسم الدور المراد تعيينه للمستخدم'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم تعيين الدور بنجاح')]
    #[OA\Response(response: 404, description: '❌ المستخدم أو الدور غير موجود')]
    #[OA\Response(response: 422, description: '❌ خطأ في البيانات')]
    public function assign(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ], [
            'role.required' => 'اسم الدور مطلوب.',
            'role.exists'   => 'الدور المحدد غير موجود في النظام.',
        ]);

        $user = User::findOrFail($userId);
        $user->assignRole($request->role);

        return $this->successResponse([
            'user_id'  => $user->id,
            'username' => $user->username,
            'roles'    => $user->getRoleNames(),
        ], 'تم تعيين الدور "' . $request->role . '" للمستخدم بنجاح.');
    }

    /**
     * DELETE /v1/users/{userId}/roles
     * Remove a role from a user.
     */
    #[OA\Delete(
        path: '/v1/users/{userId}/roles',
        summary: '➖ إزالة دور من مستخدم',
        description: 'يزيل دوراً من مستخدم معين.',
        operationId: 'revokeRoleFromUser',
        tags: ['User Roles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['role'],
            properties: [
                new OA\Property(property: 'role', type: 'string', example: 'member_manager'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم إزالة الدور بنجاح')]
    #[OA\Response(response: 404, description: '❌ المستخدم غير موجود')]
    public function revoke(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'role' => 'required|string',
        ], [
            'role.required' => 'اسم الدور مطلوب.',
        ]);

        $user = User::findOrFail($userId);
        $user->removeRole($request->role);

        return $this->successResponse([
            'user_id'  => $user->id,
            'username' => $user->username,
            'roles'    => $user->getRoleNames(),
        ], 'تم إزالة الدور "' . $request->role . '" من المستخدم بنجاح.');
    }
}
