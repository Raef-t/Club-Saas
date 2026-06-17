<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\MemberManager\Services\Me\MemberDashboardService;

class MemberDashboardController extends BaseController
{
    protected MemberDashboardService $dashboardService;

    public function __construct(MemberDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    #[OA\Get(
        path: '/v1/members/dashboard',
        summary: '🏠 لوحة تحكم العضو (الرئيسية)',
        description: 'إرجاع جميع البيانات الخاصة بلوحة تحكم العضو الحالي مثل الحصص القادمة، الاشتراكات الفعالة، الإحصائيات والفواتير غير المدفوعة.',
        tags: ['Member App'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع بيانات لوحة التحكم بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Dashboard data retrieved successfully.'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'profile_summary', type: 'object', properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'محمد أحمد'),
                            new OA\Property(property: 'member_number', type: 'string', example: 'MEM-10023'),
                            new OA\Property(property: 'qr_code', type: 'string', example: 'QR-X1Y2Z3'),
                            new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://club-saas.com/storage/profile.jpg')
                        ]),
                        new OA\Property(property: 'active_subscriptions', type: 'array', items: new OA\Items(type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'plan_name', type: 'string', example: 'اشتراك ذهبي 3 شهور'),
                            new OA\Property(property: 'remaining_days', type: 'integer', example: 45)
                        ])),
                        new OA\Property(property: 'upcoming_sessions', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'unpaid_invoices_count', type: 'integer', example: 1)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح (Unauthenticated)',
        content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')])
    )]
    #[OA\Response(
        response: 403,
        description: '🚫 لم يتم العثور على ملف العضو',
        content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member profile not found for this user.')])
    )]
    #[OA\Response(response: 500, description: '🔥 خطأ في الخادم', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'حدث خطأ داخلي في الخادم.')]))]
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse(__('Unauthorized'), 401);
        }

        // Resolve member from authenticated user (User → Person → Member)
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found for this user.'), 403);
        }

        $data = $this->dashboardService->getDashboardData(
            (int) $member->id,
            (int) $member->person_id
        );

        return $this->successResponse($data, __('Dashboard data retrieved successfully.'));
    }

    /**
     * Resolve the Member record from the authenticated user.
     * Supports both direct Member auth and User → Person → Member chain.
     */
    protected function resolveMember($user)
    {
        // If the user is a Member directly (custom guard)
        if ($user instanceof \Modules\MemberManager\Models\Member) {
            return $user;
        }

        // Standard flow: User → Person → Member
        if (isset($user->person_id)) {
            return DB::table('members')
                ->where('person_id', $user->person_id)
                ->whereNull('deleted_at')
                ->first();
        }

        return null;
    }
}
