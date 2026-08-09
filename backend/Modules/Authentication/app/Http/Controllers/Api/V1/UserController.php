<?php

namespace Modules\Authentication\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authentication\Models\User;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Users',
    description: 'إدارة واستعلام بيانات المستخدمين'
)]
class UserController extends BaseController
{
    /**
     * GET /v1/users
     * جلب قائمة جميع المستخدمين مع إمكانية الفلترة حسب الدور (role).
     */
    #[OA\Get(
        path: '/v1/users',
        summary: '👥 جلب قائمة المستخدمين مع الفلترة حسب الدور',
        description: 'يعيد قائمة جميع المستخدمين ويتضمن الاسم، اسم المستخدم، الدور، وحالة تغيير كلمة السر.',
        operationId: 'getUsersList',
        tags: ['Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'role',
        in: 'query',
        required: false,
        description: 'اسم الدور للفلترة (مثال: admin, coach, staff, player)',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(response: 200, description: '✅ تم جلب قائمة المستخدمين بنجاح')]
    public function index(Request $request): JsonResponse
    {
        $role = $request->query('role');

        $query = User::with(['person', 'roles']);

        if ($role) {
            $query->where(function ($q) use ($role) {
                $q->whereHas('roles', function ($r) use ($role) {
                    $r->where('name', $role);
                })->orWhere('role', $role);
            });
        }

        $users = $query->get()->map(function ($user) {
            $rolesList = $user->getRoleNames()->toArray();
            if (empty($rolesList) && $user->role) {
                $rolesList = [$user->role];
            }

            return [
                'id'                   => $user->id,
                'name'                 => $user->person?->full_name ?? 'N/A',
                'username'             => $user->username,
                'custom_username'      => $user->custom_username,
                'roles'                => $rolesList,
                'must_change_password' => (bool) $user->must_change_password,
                'is_password_changed'  => !(bool) $user->must_change_password,
            ];
        });

        return $this->successResponse($users, 'تم جلب قائمة المستخدمين بنجاح.');
    }
}
