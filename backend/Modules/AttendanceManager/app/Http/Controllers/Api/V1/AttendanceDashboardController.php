<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\AttendanceManager\Services\AttendanceDashboardService;
use OpenApi\Attributes as OA;

class AttendanceDashboardController extends BaseController
{
    public function __construct(
        protected AttendanceDashboardService $dashboardService
    ) {}

    #[OA\Get(
        path: '/v1/attendance-manager/dashboard-stats',
        summary: '📊 إحصائيات لوحة التحكم اللحظية للاستقبال والاشتراكات',
        description: 'يعرض إجمالي اللاعبين ذوي الاشتراكات النشطة، عدد الحاضرين في تدريب عام/خاص، الخطط الجارية حالياً وتفاصيل الحضور بها، الاشتراكات القريبة من الانتهاء، والخزائن المجانية المسندة.',
        tags: ['Reports'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الإحصائيات بنجاح', content: new OA\JsonContent())]
    #[OA\Response(response: 400, description: '❌ خطأ في معالجة الإحصائيات')]
    public function stats(Request $request)
    {
        try {
            $branchId = $request->query('branch_id');
            $stats = $this->dashboardService->getDashboardStats($branchId ? (int)$branchId : null);

            return $this->successResponse($stats, __('Dashboard stats retrieved successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
