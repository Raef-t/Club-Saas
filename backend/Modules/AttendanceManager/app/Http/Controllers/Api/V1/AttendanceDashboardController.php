<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\AttendanceManager\Services\AttendanceDashboardService;
use Modules\AttendanceManager\Services\DashboardNotificationService;
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

    #[OA\Get(
        path: '/v1/attendance-manager/dashboard-stats-stream',
        summary: '⚡ بث لحظي لإحصائيات لوحة التحكم بطريقة SSE (Server-Sent Events)',
        description: 'يفتح اتصال HTTP مستمر لإرسال تحديثات إحصائيات لوحة التحكم فور حدوث أي تعديل في الحضور، الاشتراكات، أو الخزائن.',
        tags: ['Reports'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ stream مفتوح لتقنية SSE (text/event-stream)')]
    public function statsStream(Request $request): StreamedResponse
    {
        $branchId = $request->query('branch_id') ? (int) $request->query('branch_id') : null;

        return response()->stream(function () use ($branchId) {
            set_time_limit(0);
            ignore_user_abort(true);

            $lastVersion = null;

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $currentVersion = DashboardNotificationService::getBranchStatsVersion($branchId);

                if ($lastVersion === null || $currentVersion !== $lastVersion) {
                    $lastVersion = $currentVersion;

                    $stats = $this->dashboardService->getCachedDashboardStats($branchId);

                    $payload = json_encode([
                        'status'    => 'success',
                        'event'     => 'dashboard_updated',
                        'version'   => $currentVersion,
                        'data'      => $stats,
                        'timestamp' => now()->toIso8601String(),
                    ]);

                    echo "data: {$payload}\n\n";
                } else {
                    // Heartbeat ping to keep connection alive
                    echo ": heartbeat\n\n";
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                sleep(2);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
