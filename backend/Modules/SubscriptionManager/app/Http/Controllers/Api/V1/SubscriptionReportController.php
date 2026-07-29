<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Modules\SubscriptionManager\Services\SubscriptionReportService;
use OpenApi\Attributes as OA;

class SubscriptionReportController extends BaseController
{
    protected SubscriptionReportService $reportService;

    public function __construct(SubscriptionReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    #[OA\Get(
        path: '/v1/reports/subscriptions/renewal-status',
        summary: '📊 تقرير تجديد وانقضاء الاشتراكات (Renewal & Expiration Report)',
        description: 'استرجاع تقرير تفصيلي وشامل عن اللاعبين الذين انتهت اشتراكاتهم ولم يجددوا، واللاعبين الذين جددوا، مع تفاصيل الخطة، والمدربين، والإحصائيات المالية.',
        tags: ['Reports'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'type', in: 'query', required: false, description: 'نوع التقرير: expired_non_renewed (منتهي ولم يجدد), renewed (تم التجديد), all (الجميع)', schema: new OA\Schema(type: 'string', enum: ['expired_non_renewed', 'renewed', 'all'], default: 'all'))]
    #[OA\Parameter(name: 'date_filter_by', in: 'query', required: false, description: 'المعيار الزمني للتصفية: end_date (انقضاء الاشتراك), start_date (بداية الاشتراك), created_date (تاريخ القيد)', schema: new OA\Schema(type: 'string', enum: ['end_date', 'start_date', 'created_date'], default: 'end_date'))]
    #[OA\Parameter(name: 'start_date', in: 'query', required: false, description: 'تاريخ بداية النطاق الزمني', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01'))]
    #[OA\Parameter(name: 'end_date', in: 'query', required: false, description: 'تاريخ نهاية النطاق الزمني', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-31'))]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'plan_id', in: 'query', required: false, description: 'تصفية حسب خطة الاشتراك', schema: new OA\Schema(type: 'integer', example: 5))]
    #[OA\Parameter(name: 'coach_id', in: 'query', required: false, description: 'تصفية حسب المدرب المسند', schema: new OA\Schema(type: 'integer', example: 2))]
    #[OA\Parameter(name: 'search', in: 'query', required: false, description: 'بحث برقم الهاتف، اسم اللاعب، أو رقم العضوية', schema: new OA\Schema(type: 'string', example: 'أحمد'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع التقرير بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription renewal status report retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'summary', type: 'object', properties: [
                            new OA\Property(property: 'total_records', type: 'integer', example: 25),
                            new OA\Property(property: 'total_expired_non_renewed', type: 'integer', example: 15),
                            new OA\Property(property: 'total_renewed', type: 'integer', example: 10),
                            new OA\Property(property: 'renewal_rate_percentage', type: 'number', example: 40.0),
                            new OA\Property(property: 'total_lost_potential_revenue', type: 'number', example: 2250.0),
                            new OA\Property(property: 'total_renewed_revenue', type: 'number', example: 1800.0),
                        ]),
                        new OA\Property(
                            property: 'records',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'subscription_id', type: 'integer', example: 101),
                                    new OA\Property(property: 'status_type', type: 'string', example: 'expired_non_renewed'),
                                    new OA\Property(property: 'status_label', type: 'string', example: 'منتهي ولم يجدد'),
                                    new OA\Property(property: 'member_id', type: 'integer', example: 12),
                                    new OA\Property(property: 'member_number', type: 'string', example: 'MEM-10023'),
                                    new OA\Property(property: 'member_name', type: 'string', example: 'محمد أحمد'),
                                    new OA\Property(property: 'member_phone', type: 'string', example: '0501234567'),
                                    new OA\Property(property: 'branch_name', type: 'string', example: 'الفرع الرئيسي'),
                                    new OA\Property(property: 'plan_id', type: 'integer', example: 5),
                                    new OA\Property(property: 'plan_name', type: 'string', example: 'اشتراك لياقة شهري'),
                                    new OA\Property(property: 'plan_type', type: 'string', example: 'monthly'),
                                    new OA\Property(property: 'coaches_names', type: 'string', example: 'الكابتن طارق علي'),
                                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-06-01'),
                                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-07-01'),
                                    new OA\Property(property: 'subscription_status', type: 'string', example: 'expired'),
                                    new OA\Property(property: 'days_since_expiration', type: 'integer', example: 28),
                                    new OA\Property(property: 'total_amount', type: 'number', example: 150.0),
                                    new OA\Property(property: 'paid_amount', type: 'number', example: 150.0),
                                    new OA\Property(property: 'remaining_amount', type: 'number', example: 0.0),
                                    new OA\Property(property: 'is_fully_paid', type: 'boolean', example: true),
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function renewalStatusReport(Request $request)
    {
        $filters = $request->only(['type', 'start_date', 'end_date', 'date_filter_by', 'branch_id', 'plan_id', 'coach_id', 'search']);
        $reportData = $this->reportService->getRenewalStatusReport($filters);

        return $this->successResponse($reportData, __('Subscription renewal status report retrieved successfully'));
    }
}
