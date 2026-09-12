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
        name: 'search',
        in: 'query',
        required: false,
        description: 'بحث شامل بالاسم أو اسم المستخدم',
        schema: new OA\Schema(type: 'string', example: 'أحمد')
    )]
    #[OA\Parameter(
        name: 'name',
        in: 'query',
        required: false,
        description: 'بحث بالاسم الشخصي',
        schema: new OA\Schema(type: 'string', example: 'أحمد')
    )]
    #[OA\Parameter(
        name: 'username',
        in: 'query',
        required: false,
        description: 'بحث باسم المستخدم',
        schema: new OA\Schema(type: 'string', example: 'ahmed123')
    )]
    #[OA\Parameter(
        name: 'is_active',
        in: 'query',
        required: false,
        description: "تصفية حسب حالة تفعيل الحساب:
- `true` أو `1`: إظهار المستخدمين النشطين فقط
- `false` أو `0`: إظهار المستخدمين الموقوفين فقط
- ترك الحقل فارغاً: إظهار جميع المستخدمين",
        schema: new OA\Schema(type: 'boolean', example: true)
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب قائمة المستخدمين بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'تم جلب قائمة المستخدمين بنجاح.',
                'data' => [
                    [
                        'id' => 5,
                        'name' => 'أحمد محمد العلي',
                        'username' => 'tec-ply-10023',
                        'custom_username' => null,
                        'roles' => ['player'],
                        'branch_id' => 1,
                        'is_active' => true,
                        'must_change_password' => false,
                        'is_password_changed' => true
                    ]
                ]
            ]
        )
    )]
    public function index(Request $request): JsonResponse
    {
        $role = $request->query('role');
        $branchId = $request->query('branch_id');

        $query = User::with(['person.member', 'person.staff.branches', 'roles']);

        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

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

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('custom_username', 'like', "%{$search}%")
                  ->orWhereHas('person', function ($pq) use ($search) {
                      $pq->where('full_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('name')) {
            $name = trim((string) $request->input('name'));
            $query->whereHas('person', function ($pq) use ($name) {
                $pq->where('full_name', 'like', "%{$name}%");
            });
        }

        if ($request->filled('username')) {
            $username = trim((string) $request->input('username'));
            $query->where(function ($uq) use ($username) {
                $uq->where('username', 'like', "%{$username}%")
                   ->orWhere('custom_username', 'like', "%{$username}%");
            });
        }

        $mapUser = function ($user) {
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
                'is_active'            => (bool) $user->is_active,
                'must_change_password' => (bool) $user->must_change_password,
                'is_password_changed'  => !(bool) $user->must_change_password,
            ];
        };

        if ($request->has('per_page') && $request->input('per_page') !== 'all') {
            $perPage = min(max((int) $request->input('per_page'), 1), 100);
            $paginator = $query->paginate($perPage);
            $users = $paginator->through($mapUser);
        } else {
            $users = $query->get()->map($mapUser);
        }

        return $this->successResponse($users, 'تم جلب قائمة المستخدمين بنجاح.');
    }

    /**
     * PATCH /v1/users/{id}/toggle-status
     * تبديل حالة تفعيل حساب المستخدم (تفعيل / إيقاف).
     */
    #[OA\Patch(
        path: '/v1/users/{id}/toggle-status',
        summary: '🔄 تبديل حالة تفعيل حساب المستخدم (تفعيل / إيقاف)',
        description: "يقوم هذا المسار بتبديل حالة الحساب بين نشط وموقوف لأي مستخدم بغض النظر عن دوره:
- **في حال الإيقاف (`is_active = false`)**: يتم فوراً إبطال وحذف جميع التوكنات والجلسات الفعالة للمستخدم في جدول personal_access_tokens، وطرده من التطبيق والموقع فوراً. محاولات تسجيل الدخول القادمة تُرفض بكود 403 Forbidden ورسالة 'User account is inactive'.
- **في حال التفعيل (`is_active = true`)**: يتم إعادة تنشيط الحساب ليتمكن من تسجيل الدخول مجدداً بصورة طبيعية.
- **الحماية**: يمنع المشرف من تعطيل حسابه الشخصي بنفسه، ويمنع تعطيل حساب المدير العام (super_admin).",
        operationId: 'toggleUserStatus',
        tags: ['Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'معرف المستخدم المراد تبديل حالته (User ID)',
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تغيير حالة تفعيل المستخدم بنجاح',
        content: new OA\JsonContent(
            examples: [
                new OA\Examples(
                    example: 'deactivated',
                    summary: 'حالة الإيقاف (Deactivated)',
                    value: [
                        'status' => 'success',
                        'message' => 'تم إيقاف الحساب وإلغاء جميع جلسات الدخول الفعالة بنجاح.',
                        'data' => [
                            'id' => 5,
                            'username' => 'tec-ply-10023',
                            'is_active' => false
                        ]
                    ]
                ),
                new OA\Examples(
                    example: 'activated',
                    summary: 'حالة التفعيل (Activated)',
                    value: [
                        'status' => 'success',
                        'message' => 'تم تفعيل الحساب بنجاح.',
                        'data' => [
                            'id' => 5,
                            'username' => 'tec-ply-10023',
                            'is_active' => true
                        ]
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ تحقق: محاولة إيقاف الحساب الشخصي أو حساب السوبر أدمن',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'لا يمكنك تغيير حالة تفعيل حسابك الشخصي.'
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '❌ المستخدم غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'المستخدم غير موجود.'
            ]
        )
    )]
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($request->user() && $request->user()->id === $user->id) {
            return $this->errorResponse(__('لا يمكنك تغيير حالة تفعيل حسابك الشخصي.'), 422);
        }

        if ($user->hasRole('super_admin') || $user->role === 'super_admin') {
            return $this->errorResponse(__('لا يمكن إيقاف تفعيل حساب المدير العام (Super Admin).'), 422);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        if (!$user->is_active) {
            // Force logout: Revoke all active tokens
            $user->tokens()->delete();
        }

        return $this->successResponse([
            'id'        => $user->id,
            'username'  => $user->username,
            'is_active' => (bool) $user->is_active,
        ], $user->is_active ? __('تم تفعيل الحساب بنجاح.') : __('تم إيقاف الحساب وإلغاء جميع جلسات الدخول الفعالة بنجاح.'));
    }

    /**
     * DELETE /v1/users/{id}
     * حذف مستخدم (Soft Delete).
     */
    #[OA\Delete(
        path: '/v1/users/{id}',
        summary: '🗑️ إخفاء المستخدم من النظام (حذف مرن Soft Delete)',
        description: "يقوم هذا المسار بإخفاء المستخدم من النظام بالكامل دون حذف بياناته التاريخية:
- **الحذف المرن (Soft Delete)**: يتم تسجيل وقت الحذف في deleted_at ويختفي المستخدم تلقائياً من كافة قوائم النظام والبحث ومنسدلات الاختيار.
- **إلغاء الجلسات**: يتم سحب كافة التوكنات فوراً وضبط is_active = false لمنع تسجيل الدخول.
- **شروط الأمان**: يتطلب إرسال تأكيد صريح confirmation: 'delete' في الـ Request Body لحماية البيانات من الحذف العرضي، ويمنع حذف الحساب الشخصي أو حساب السوبر أدمن.",
        operationId: 'softDeleteUser',
        tags: ['Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'معرف المستخدم المراد حذفه وإخفاؤه',
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'تأكيد الحذف بإرسال كلمة delete',
        content: new OA\JsonContent(
            required: ['confirmation'],
            properties: [
                new OA\Property(property: 'confirmation', type: 'string', example: 'delete', description: 'يجب كتابة كلمة delete حصراً لتأكيد العملية')
            ],
            example: [
                'confirmation' => 'delete'
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم إخفاء وحذف المستخدم بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'تم إخفاء وحذف المستخدم بنجاح.',
                'data' => null
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ لم يتم إرسال تأكيد الحذف أو محاولة حذف الحساب الشخصي',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'يجب إرسال كلمة "delete" لتأكيد عملية حذف المستخدم.'
            ]
        )
    )]
    public function destroy(Request $request, $id): JsonResponse
    {
        $confirmation = $request->input('confirmation', '');
        if (strtolower(trim((string) $confirmation)) !== 'delete') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'confirmation' => __('يجب إرسال كلمة "delete" لتأكيد عملية حذف المستخدم.')
            ]);
        }

        $targetId = (int) $id;
        if ($request->user() && $request->user()->id === $targetId) {
            return $this->errorResponse(__('لا يمكنك حذف حسابك الشخصي.'), 422);
        }

        $user = User::findOrFail($targetId);

        if ($user->hasRole('super_admin') || $user->role === 'super_admin') {
            return $this->errorResponse(__('لا يمكن حذف حساب المدير العام (Super Admin).'), 422);
        }

        // Revoke active sessions & mark inactive
        $user->tokens()->delete();
        $user->is_active = false;
        $user->save();
        $user->delete();

        return $this->successResponse(null, __('تم إخفاء وحذف المستخدم بنجاح.'));
    }

    /**
     * POST /v1/users/{id}/restore
     * استرجاع مستخدم محذوف من سلة المهملات وإعادة تفعيل حسابه.
     */
    #[OA\Post(
        path: '/v1/users/{id}/restore',
        summary: '♻️ استرجاع مستخدم محذوف من سلة المهملات',
        description: "استرجاع المستخدم الذي تم حذفه مرناً (Soft Deleted) من سلة المهملات:
- يتم تصفير حقل deleted_at = null (إلغاء الحذف).
- يتم إعادة تفعيل حسابه تلقائياً (is_active = true).
- يعود المستخدم للظهور فوراً في قوائم النظام وشاشات الاستقبال والتقارير اليومية ويستطيع تسجيل الدخول من جديد.",
        operationId: 'restoreUser',
        tags: ['Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'معرف المستخدم المحذوف المراد استرجاعه',
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع المستخدم وتفعيل حسابه بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'تم استرجاع المستخدم وتفعيل حسابه بنجاح.',
                'data' => [
                    'id' => 5,
                    'username' => 'tec-ply-10023',
                    'is_active' => true
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '❌ المستخدم غير موجود في سلة المهملات',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'المستخدم المحذوف غير موجود.'
            ]
        )
    )]
    public function restore($id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        $user->is_active = true;
        $user->save();

        return $this->successResponse([
            'id'        => $user->id,
            'username'  => $user->username,
            'is_active' => (bool) $user->is_active,
        ], __('تم استرجاع المستخدم وتفعيل حسابه بنجاح.'));
    }

    /**
     * GET /v1/users/trashed
     * جلب قائمة المستخدمين المحذوفين مرناً.
     */
    #[OA\Get(
        path: '/v1/users/trashed',
        summary: '🗑️ استعراض قائمة المستخدمين المحذوفين (سلة المهملات)',
        description: "استعراض قائمة بجميع المستخدمين المحذوفين مرناً في النظام مع إظهار تاريخ الحذف deleted_at والبيانات الشخصية والدور لتسهيل معاينتهم أو تحديد من ترغب باسترجاعه.",
        operationId: 'getTrashedUsers',
        tags: ['Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب قائمة المستخدمين المحذوفين بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'تم جلب قائمة المستخدمين المحذوفين بنجاح.',
                'data' => [
                    [
                        'id' => 5,
                        'name' => 'أحمد محمد العلي',
                        'username' => 'tec-ply-10023',
                        'custom_username' => null,
                        'roles' => ['player'],
                        'branch_id' => 1,
                        'is_active' => false,
                        'deleted_at' => '2026-09-12 12:15:30',
                        'must_change_password' => false,
                        'is_password_changed' => true
                    ]
                ]
            ]
        )
    )]
    public function trashed(Request $request): JsonResponse
    {
        $query = User::onlyTrashed()->with(['person.member', 'person.staff.branches', 'roles']);

        $mapUser = function ($user) {
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
                'is_active'            => (bool) $user->is_active,
                'deleted_at'           => $user->deleted_at?->toDateTimeString(),
                'must_change_password' => (bool) $user->must_change_password,
                'is_password_changed'  => !(bool) $user->must_change_password,
            ];
        };

        if ($request->has('per_page') && $request->input('per_page') !== 'all') {
            $perPage = min(max((int) $request->input('per_page'), 1), 100);
            $paginator = $query->paginate($perPage);
            $users = $paginator->through($mapUser);
        } else {
            $users = $query->get()->map($mapUser);
        }

        return $this->successResponse($users, __('تم جلب قائمة المستخدمين المحذوفين بنجاح.'));
    }
}


