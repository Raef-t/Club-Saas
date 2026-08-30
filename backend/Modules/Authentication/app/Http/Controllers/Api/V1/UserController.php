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
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'معرف الفرع للفلترة',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'عدد العناصر في الصفحة (الافتراضي: 15)',
        schema: new OA\Schema(type: 'integer', example: 15)
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'رقم الصفحة',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(response: 200, description: '✅ تم جلب قائمة المستخدمين بنجاح')]
    public function index(Request $request): JsonResponse
    {
        $role = $request->query('role');
        $branchId = $request->query('branch_id');

        $query = User::with(['person.member', 'person.staff.branches', 'roles']);

        if ($role) {
            $query->where(function ($q) use ($role) {
                $q->whereHas('roles', function ($r) use ($role) {
                    $r->where('name', $role);
                })->orWhere('role', $role);
            });
        }

        if ($branchId) {
            $query->whereHas('person', function ($p) use ($branchId) {
                $p->whereHas('member', function ($m) use ($branchId) {
                    $m->where('branch_id', $branchId);
                })->orWhereHas('staff', function ($s) use ($branchId) {
                    $s->whereHas('branches', function ($b) use ($branchId) {
                        $b->where('branches.id', $branchId);
                    });
                });
            });
        }

        $perPage = $this->getPerPage($request);
        $users = $query->paginate($perPage)->through(function ($user) {
            $rolesList = $user->getRoleNames()->toArray();
            if (empty($rolesList) && $user->role) {
                $rolesList = [$user->role];
            }

            $userBranchId = $user->person?->member?->branch_id 
                ?? $user->person?->staff?->branches?->first()?->id 
                ?? null;

            return [
                'id'                   => $user->id,
                'name'                 => $user->person?->full_name ?? 'N/A',
                'username'             => $user->username,
                'custom_username'      => $user->custom_username,
                'roles'                => $rolesList,
                'branch_id'            => $userBranchId,
                'must_change_password' => (bool) $user->must_change_password,
                'is_password_changed'  => !(bool) $user->must_change_password,
            ];
        });

        return $this->successResponse($users, 'تم جلب قائمة المستخدمين بنجاح.');
    }
}


