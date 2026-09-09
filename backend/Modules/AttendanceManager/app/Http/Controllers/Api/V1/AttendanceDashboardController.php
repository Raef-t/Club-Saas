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
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'معرف الفرع (اختياري، في حال تركه فارغاً يتم جلب إحصائيات جميع الفروع)',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الإحصائيات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Dashboard stats retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total_active_subscribed_members', type: 'integer', example: 142, description: 'إجمالي الأعضاء ذوي الاشتراكات السارية'),
                        new OA\Property(property: 'realtime_training_players_count', type: 'integer', example: 18, description: 'عدد اللاعبين المتواجدين حالياً في الصالة (تدريب حر/عام)'),
                        new OA\Property(property: 'expiring_subscriptions_count', type: 'integer', example: 7, description: 'الاشتراكات التي اقتربت من الانتهاء (<= 3 حصص أو <= 7 أيام)'),
                        new OA\Property(property: 'free_assigned_player_lockers_count', type: 'integer', example: 12, description: 'عدد الخزائن المجانية المسندة والنشطة للاعبين'),
                        new OA\Property(
                            property: 'current_active_session_plans',
                            type: 'array',
                            description: 'الحصص التدريبية الجارية حالياً في هذه اللحظة',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'plan_id', type: 'integer', example: 5),
                                    new OA\Property(property: 'plan_name', type: 'string', example: 'كيك بوكسينغ - متقدم'),
                                    new OA\Property(property: 'session_template_id', type: 'integer', example: 14),
                                    new OA\Property(property: 'start_time', type: 'string', example: '12:00:00'),
                                    new OA\Property(property: 'end_time', type: 'string', example: '13:30:00'),
                                    new OA\Property(property: 'present_players_count', type: 'integer', example: 6, description: 'عدد اللاعبين الحاضرين في هذه الحصة حالياً')
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
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
        description: "يفتح اتصال HTTP مستمر لإرسال تحديثات إحصائيات لوحة التحكم فور حدوث أي تعديل في الحضور، الاشتراكات، أو الخزائن.\n\n" .
                     "• الرسائل الأولية: إشعار الاتصال `: connected`.\n" .
                     "• نبضات إبقاء الاتصال (Heartbeat): ترسل كل ثانية `: heartbeat` للحفاظ على الاتصال حياً.\n" .
                     "• رسائل التحديث: ترسل في أسطر تبدأ بـ `data: ` تحتوي على كائن JSON بالهيكل الموضح أدناه.",
        tags: ['Reports'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'معرف الفرع (اختياري، في حال تركه فارغاً يتم جلب إحصائيات جميع الفروع)',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ بث SSE مباشر ومستمر (text/event-stream)',
        content: [
            new OA\MediaType(
                mediaType: 'text/event-stream',
                schema: new OA\Schema(
                    type: 'string',
                    description: 'نمط البث اللحظي SSE مع بيانات JSON منسقة داخل حقل data',
                    example: "data: {\n  \"status\": \"success\",\n  \"event\": \"dashboard_updated\",\n  \"version\": 1741512345678,\n  \"timestamp\": \"2026-09-09T12:35:00+03:00\",\n  \"data\": {\n    \"total_active_subscribed_members\": 142,\n    \"realtime_training_players_count\": 18,\n    \"expiring_subscriptions_count\": 7,\n    \"free_assigned_player_lockers_count\": 12,\n    \"current_active_session_plans\": [\n      {\n        \"plan_id\": 5,\n        \"plan_name\": \"كيك بوكسينغ - متقدم\",\n        \"session_template_id\": 14,\n        \"start_time\": \"12:00:00\",\n        \"end_time\": \"13:30:00\",\n        \"present_players_count\": 6\n      }\n    ]\n  }\n}\n\n"
                )
            ),
            new OA\JsonContent(
                description: 'هيكل كائن الـ JSON المستلم عبر الـ Event (للمعاينة التفاعلية الملونة)',
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'event', type: 'string', example: 'dashboard_updated'),
                    new OA\Property(property: 'version', type: 'integer', example: 1741512345678, description: 'رقم إصدار التحديث (Timestamp بالملي ثانية)'),
                    new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2026-09-09T12:35:00+03:00'),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'total_active_subscribed_members', type: 'integer', example: 142, description: 'إجمالي الأعضاء ذوي الاشتراكات السارية'),
                            new OA\Property(property: 'realtime_training_players_count', type: 'integer', example: 18, description: 'عدد اللاعبين المتواجدين حالياً في الصالة (تدريب حر/عام)'),
                            new OA\Property(property: 'expiring_subscriptions_count', type: 'integer', example: 7, description: 'الاشتراكات التي اقتربت من الانتهاء (<= 3 حصص أو <= 7 أيام)'),
                            new OA\Property(property: 'free_assigned_player_lockers_count', type: 'integer', example: 12, description: 'عدد الخزائن المجانية المسندة والنشطة للاعبين'),
                            new OA\Property(
                                property: 'current_active_session_plans',
                                type: 'array',
                                description: 'الحصص التدريبية الجارية حالياً في هذه اللحظة',
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'plan_id', type: 'integer', example: 5),
                                        new OA\Property(property: 'plan_name', type: 'string', example: 'كيك بوكسينغ - متقدم'),
                                        new OA\Property(property: 'session_template_id', type: 'integer', example: 14),
                                        new OA\Property(property: 'start_time', type: 'string', example: '12:00:00'),
                                        new OA\Property(property: 'end_time', type: 'string', example: '13:30:00'),
                                        new OA\Property(property: 'present_players_count', type: 'integer', example: 6, description: 'عدد اللاعبين الحاضرين في هذه الحصة حالياً')
                                    ]
                                )
                            )
                        ]
                    )
                ]
            )
        ]
    )]
    public function statsStream(Request $request): StreamedResponse
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $branchId = $request->query('branch_id');

        $branchId = $branchId !== null
            ? (int) $branchId
            : null;

        return response()->stream(
            function () use ($branchId) {
                set_time_limit(0);
                ignore_user_abort(false);

                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

                echo ": connected\n\n";
                flush();

                $lastVersion = null;
                $lastMinute = null;
                $pollInterval = 1;

                while (true) {
                    if (connection_aborted()) {
                        break;
                    }

                    $currentVersion =
                        DashboardNotificationService::getBranchStatsVersion(
                            $branchId
                        );
                    $currentMinute = now()->format('Y-m-d H:i');

                    if (
                        $lastVersion === null ||
                        $currentVersion !== $lastVersion ||
                        $currentMinute !== $lastMinute
                    ) {
                        $lastVersion = $currentVersion;
                        $lastMinute = $currentMinute;

                        $stats = $this->dashboardService
                            ->getCachedDashboardStats($branchId);

                        $payload = json_encode(
                            [
                                'status' => 'success',
                                'event' => 'dashboard_updated',
                                'version' => $currentVersion,
                                'data' => $stats,
                                'timestamp' => now()->toIso8601String(),
                            ],
                            JSON_UNESCAPED_UNICODE |
                                JSON_UNESCAPED_SLASHES |
                                JSON_THROW_ON_ERROR
                        );

                        echo "data: {$payload}\n\n";
                    } else {
                        echo ": heartbeat\n\n";
                    }

                    flush();

                    sleep($pollInterval);
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
                'Content-Encoding' => 'none',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }
}
