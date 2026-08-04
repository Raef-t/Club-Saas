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
        path: '/v1/reports/subscriptions',
        summary: '📋 تقرير الاشتراكات الشامل (Subscriptions Comprehensive Report)',
        description: 'استرجاع تقرير تفصيلي وشامل عن كافة الاشتراكات يتضمن معلومات اللاعب، حالة الاشتراك، تفاصيل الجلسات المخصصة والمتبقية، المدربين المسندين، والمبالغ المالية (المدفوع والمتبقي وحالة الدفع) مع فلاتر اختيارية متكاملة.',
        tags: ['Reports'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'تصفية بحالة الاشتراك: active (فعال), finished (منتهي), frozen (مجمد), terminated (ملغى), all (الجميع)', schema: new OA\Schema(type: 'string', enum: ['all', 'active', 'finished', 'frozen', 'terminated'], default: 'all'))]
    #[OA\Parameter(name: 'plan_id', in: 'query', required: false, description: 'تصفية حسب خطة الاشتراك', schema: new OA\Schema(type: 'integer', example: 5))]
    #[OA\Parameter(name: 'payment_status', in: 'query', required: false, description: 'تصفية بحالة الدفع: paid (مدفوع بالكامل), partially_paid (مدفوع جزئياً), unpaid (غير مدفوع), all (الجميع)', schema: new OA\Schema(type: 'string', enum: ['all', 'paid', 'partially_paid', 'unpaid'], default: 'all'))]
    #[OA\Parameter(name: 'coach_id', in: 'query', required: false, description: 'تصفية حسب الكوتش المسند', schema: new OA\Schema(type: 'integer', example: 2))]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'start_date', in: 'query', required: false, description: 'تاريخ بداية النطاق الزمني (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01'))]
    #[OA\Parameter(name: 'end_date', in: 'query', required: false, description: 'تاريخ نهاية النطاق الزمني (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-31'))]
    #[OA\Parameter(name: 'search', in: 'query', required: false, description: 'بحث باسم اللاعب، رقم الهاتف، أو رقم العضوية', schema: new OA\Schema(type: 'string', example: 'أحمد'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تقرير الاشتراكات الشامل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'All subscriptions report retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'summary', type: 'object', properties: [
                            new OA\Property(property: 'total_subscriptions', type: 'integer', example: 120),
                            new OA\Property(property: 'total_revenue', type: 'number', example: 15000.0),
                            new OA\Property(property: 'total_paid', type: 'number', example: 12000.0),
                            new OA\Property(property: 'total_remaining', type: 'number', example: 3000.0),
                            new OA\Property(property: 'active_count', type: 'integer', example: 80),
                            new OA\Property(property: 'finished_count', type: 'integer', example: 25),
                            new OA\Property(property: 'frozen_count', type: 'integer', example: 10),
                            new OA\Property(property: 'terminated_count', type: 'integer', example: 5),
                            new OA\Property(property: 'fully_paid_count', type: 'integer', example: 95),
                            new OA\Property(property: 'partially_paid_count', type: 'integer', example: 15),
                            new OA\Property(property: 'unpaid_count', type: 'integer', example: 10),
                        ]),
                        new OA\Property(property: 'records', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function allSubscriptionsReport(Request $request)
    {
        $filters = $request->only([
            'status', 'plan_id', 'payment_status', 'coach_id', 'branch_id',
            'start_date', 'end_date', 'search'
        ]);

        $reportData = $this->reportService->getAllSubscriptionsReport($filters);

        return $this->successResponse($reportData, __('All subscriptions report retrieved successfully'));
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
                                    new OA\Property(
                                        property: 'contact_persons',
                                        type: 'array',
                                        items: new OA\Items(
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'id', type: 'integer', nullable: true, example: 1),
                                                new OA\Property(property: 'name', type: 'string', example: 'أبو محمد'),
                                                new OA\Property(property: 'phone_number', type: 'string', example: '0501234567'),
                                                new OA\Property(property: 'relation', type: 'string', nullable: true, example: 'أب'),
                                            ]
                                        )
                                    ),
                                    new OA\Property(
                                        property: 'absence_period',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'last_attendance_date', type: 'string', nullable: true, example: '2026-06-15 17:30:00'),
                                            new OA\Property(property: 'years', type: 'integer', nullable: true, example: 0),
                                            new OA\Property(property: 'months', type: 'integer', nullable: true, example: 1),
                                            new OA\Property(property: 'days', type: 'integer', nullable: true, example: 16),
                                            new OA\Property(property: 'total_days', type: 'integer', nullable: true, example: 46),
                                            new OA\Property(property: 'formatted', type: 'string', example: '0 سنة، 1 شهر، 16 يوم'),
                                        ]
                                    ),
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

    #[OA\Get(
        path: '/v1/reports/sessions/time-capacity',
        summary: '🕒 تقرير سعة الحصص والخطط مجمعة حسب الأنشطة والمدربين (Time-Slot Capacity Report by Activity)',
        description: 'استرجاع تقرير بالسعة والاستيعاب مجمعاً حسب النشاط الرياضي، المدرب، والخطط مع أوقات الحصص وعدد المشتركين النشطين الحاليين.',
        tags: ['Reports'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'start_time', in: 'query', required: false, description: 'وقت البداية لتصفية الحصص (مثل 10:00:00)', schema: new OA\Schema(type: 'string', example: '10:00:00'))]
    #[OA\Parameter(name: 'end_time', in: 'query', required: false, description: 'وقت النهاية لتصفية الحصص (مثل 14:00:00)', schema: new OA\Schema(type: 'string', example: '14:00:00'))]
    #[OA\Parameter(name: 'day_of_week', in: 'query', required: false, description: 'يوم الأسبوع (0=الأحد، 1=الاثنين ... 6=السبت)', schema: new OA\Schema(type: 'integer', minimum: 0, maximum: 6, example: 0))]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'plan_id', in: 'query', required: false, description: 'تصفية حسب خطة محددة', schema: new OA\Schema(type: 'integer', example: 5))]
    #[OA\Parameter(name: 'activity_id', in: 'query', required: false, description: 'تصفية حسب نشاط رياضي محدد', schema: new OA\Schema(type: 'integer', example: 2))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تقرير سعة الحصص بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Time-slot capacity report retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'summary', type: 'object', properties: [
                            new OA\Property(property: 'total_activities', type: 'integer', example: 3),
                            new OA\Property(property: 'total_coaches', type: 'integer', example: 5),
                            new OA\Property(property: 'total_plans', type: 'integer', example: 8),
                            new OA\Property(property: 'total_active_subscribers', type: 'integer', example: 45),
                        ]),
                        new OA\Property(
                            property: 'activities',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'activity_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'activity_name', type: 'string', example: 'كرة قدم'),
                                    new OA\Property(
                                        property: 'coaches',
                                        type: 'array',
                                        items: new OA\Items(
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'staff_id', type: 'integer', example: 2),
                                                new OA\Property(property: 'coach_name', type: 'string', example: 'الكابتن أحمد علي'),
                                                new OA\Property(property: 'staff_activity_id', type: 'integer', example: 5),
                                                new OA\Property(
                                                    property: 'plans',
                                                    type: 'array',
                                                    items: new OA\Items(
                                                        type: 'object',
                                                        properties: [
                                                            new OA\Property(property: 'plan_id', type: 'integer', example: 10),
                                                            new OA\Property(property: 'plan_name', type: 'string', example: 'اشتراك كرة قدم - المستوى الأول'),
                                                            new OA\Property(property: 'plan_type', type: 'string', example: 'monthly'),
                                                            new OA\Property(property: 'active_subscribers_count', type: 'integer', example: 15),
                                                            new OA\Property(
                                                                property: 'schedules',
                                                                type: 'array',
                                                                items: new OA\Items(
                                                                    type: 'object',
                                                                    properties: [
                                                                        new OA\Property(property: 'session_template_id', type: 'integer', example: 101),
                                                                        new OA\Property(property: 'day_of_week', type: 'integer', example: 0),
                                                                        new OA\Property(property: 'day_name', type: 'string', example: 'الأحد'),
                                                                        new OA\Property(property: 'start_time', type: 'string', example: '16:00:00'),
                                                                        new OA\Property(property: 'end_time', type: 'string', example: '17:30:00'),
                                                                        new OA\Property(property: 'facility_name', type: 'string', example: 'الملعب الرئيسي'),
                                                                    ]
                                                                )
                                                            )
                                                        ]
                                                    )
                                                )
                                            ]
                                        )
                                    )
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function timeCapacityReport(Request $request)
    {
        $filters = $request->only(['start_time', 'end_time', 'day_of_week', 'branch_id', 'plan_id', 'activity_id']);
        $reportData = $this->reportService->getTimeSlotCapacityReport($filters);

        return $this->successResponse($reportData, __('Time-slot capacity report retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/reports/attendance/peak-hours',
        summary: '🔥 تقرير أوقات الذروة والانخفاض في النادي (Peak & Off-Peak Traffic Report)',
        description: 'استرجاع تقرير ذكي يُظهر أكثر الساعات والأيام ازدحاماً وهدوءاً في النادي مع استبعاد العطل الرسمية والأسبوعية المسجلة للفرع تلقائياً.',
        tags: ['Reports'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'start_date', in: 'query', required: false, description: 'تاريخ بداية التحليل (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01'))]
    #[OA\Parameter(name: 'end_date', in: 'query', required: false, description: 'تاريخ نهاية التحليل (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-31'))]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'attendable_type', in: 'query', required: false, description: 'تصفية بين الأعضاء (member) أو الكباتن (staff)', schema: new OA\Schema(type: 'string', enum: ['member', 'staff'], default: 'member'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تقرير الذروة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Peak hours report retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'summary', type: 'object', properties: [
                            new OA\Property(property: 'busiest_day', type: 'string', example: 'الاثنين'),
                            new OA\Property(property: 'quietest_day', type: 'string', example: 'الجمعة'),
                            new OA\Property(property: 'peak_hours_range', type: 'string', example: '18:00'),
                            new OA\Property(property: 'off_peak_hours_range', type: 'string', example: '13:00'),
                            new OA\Property(property: 'total_attendances_analyzed', type: 'integer', example: 1450),
                            new OA\Property(property: 'excluded_holidays_count', type: 'integer', example: 2),
                        ]),
                        new OA\Property(property: 'top_peak_hours', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'top_off_peak_hours', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'hourly_breakdown', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'daily_breakdown', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function peakHoursReport(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date', 'branch_id', 'attendable_type']);
        $reportData = $this->reportService->getPeakHoursReport($filters);

        return $this->successResponse($reportData, __('Peak hours report retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/reports/subscriptions/frozen-terminated',
        summary: '❄️❌ تقرير الاشتراكات المجمدة والملغاة (Frozen & Terminated Subscriptions Report)',
        description: 'استرجاع تقرير تفصيلي عن كافة الاشتراكات المجمدة والملغاة (Terminated) مع إظهار أسباب التجميد والإلغاء والتوارخ والإحصائيات المالية المفقودة والمجمدة.',
        tags: ['Reports'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'تصفية حسب الحالة: frozen (المجمدة فقط), terminated (الملغاة فقط), all (الجميع)', schema: new OA\Schema(type: 'string', enum: ['all', 'frozen', 'terminated'], default: 'all'))]
    #[OA\Parameter(name: 'date_filter_by', in: 'query', required: false, description: 'المعيار الزمني للتصفية: event_date (تاريخ حدوث التجميد/الإلغاء), start_date (بداية الاشتراك), end_date (نهاية الاشتراك), created_date (تاريخ القيد)', schema: new OA\Schema(type: 'string', enum: ['event_date', 'start_date', 'end_date', 'created_date'], default: 'event_date'))]
    #[OA\Parameter(name: 'start_date', in: 'query', required: false, description: 'تاريخ بداية النطاق الزمني (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01'))]
    #[OA\Parameter(name: 'end_date', in: 'query', required: false, description: 'تاريخ نهاية النطاق الزمني (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-31'))]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'plan_id', in: 'query', required: false, description: 'تصفية حسب خطة الاشتراك', schema: new OA\Schema(type: 'integer', example: 5))]
    #[OA\Parameter(name: 'search', in: 'query', required: false, description: 'بحث برقم الهاتف، اسم اللاعب، أو رقم العضوية', schema: new OA\Schema(type: 'string', example: 'أحمد'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع التقرير بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Frozen and terminated subscriptions report retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'summary', type: 'object', properties: [
                            new OA\Property(property: 'total_records', type: 'integer', example: 15),
                            new OA\Property(property: 'total_frozen', type: 'integer', example: 10),
                            new OA\Property(property: 'total_terminated', type: 'integer', example: 5),
                            new OA\Property(property: 'total_frozen_revenue', type: 'number', example: 5000.0),
                            new OA\Property(property: 'total_lost_terminated_revenue', type: 'number', example: 2500.0),
                        ]),
                        new OA\Property(property: 'records', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function frozenAndTerminatedReport(Request $request)
    {
        $filters = $request->only(['status', 'start_date', 'end_date', 'date_filter_by', 'branch_id', 'plan_id', 'search']);
        $reportData = $this->reportService->getFrozenAndTerminatedReport($filters);

        return $this->successResponse($reportData, __('Frozen and terminated subscriptions report retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/reports/shifts/attendance',
        summary: '🌅 تقرير حضور ورديات الأنشطة وازدحامها (Shift Attendance & Crowd Report)',
        description: 'استرجاع تقرير تفصيلي يُظهر عدد اللاعبين الحاضرين في كل وردية (Shift) مع إظهار الوردية الأكثر والأقل ازدحاماً، مع إمكانية الفلترة حسب يوم محدد، شهر، أو رينج تاريخ.',
        tags: ['Reports'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'date', in: 'query', required: false, description: 'تصفية ليوم محدد (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-30'))]
    #[OA\Parameter(name: 'month', in: 'query', required: false, description: 'تصفية لشهر محدد (YYYY-MM)', schema: new OA\Schema(type: 'string', example: '2026-07'))]
    #[OA\Parameter(name: 'start_date', in: 'query', required: false, description: 'تاريخ بداية النطاق الزمني (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01'))]
    #[OA\Parameter(name: 'end_date', in: 'query', required: false, description: 'تاريخ نهاية النطاق الزمني (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-31'))]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'activity_id', in: 'query', required: false, description: 'تصفية حسب النشاط المتبع للورديات', schema: new OA\Schema(type: 'integer', example: 3))]
    #[OA\Parameter(name: 'shift_id', in: 'query', required: false, description: 'تصفية حسب وردية محددة', schema: new OA\Schema(type: 'integer', example: 2))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تقرير حضور الورديات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Shift attendance report retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'summary', type: 'object', properties: [
                            new OA\Property(property: 'period_label', type: 'string', example: 'من 2026-07-01 إلى 2026-07-31'),
                            new OA\Property(property: 'total_shift_attendances', type: 'integer', example: 450),
                            new OA\Property(property: 'total_shifts_count', type: 'integer', example: 6),
                            new OA\Property(property: 'busiest_shift', type: 'object', properties: [
                                new OA\Property(property: 'shift_id', type: 'integer', example: 2),
                                new OA\Property(property: 'shift_name', type: 'string', example: 'الوردية المسائية'),
                                new OA\Property(property: 'branch_name', type: 'string', example: 'الفرع الرئيسي'),
                                new OA\Property(property: 'attended_players_count', type: 'integer', example: 180),
                                new OA\Property(property: 'crowd_percentage', type: 'string', example: '40%'),
                            ]),
                            new OA\Property(property: 'quietest_shift', type: 'object', properties: [
                                new OA\Property(property: 'shift_id', type: 'integer', example: 1),
                                new OA\Property(property: 'shift_name', type: 'string', example: 'الوردية الصباحية'),
                                new OA\Property(property: 'branch_name', type: 'string', example: 'الفرع الرئيسي'),
                                new OA\Property(property: 'attended_players_count', type: 'integer', example: 40),
                                new OA\Property(property: 'crowd_percentage', type: 'string', example: '8.89%'),
                            ]),
                        ]),
                        new OA\Property(property: 'records', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function shiftAttendanceReport(Request $request)
    {
        $filters = $request->only(['date', 'month', 'start_date', 'end_date', 'branch_id', 'activity_id', 'shift_id']);
        $reportData = $this->reportService->getShiftAttendanceReport($filters);

        return $this->successResponse($reportData, __('Shift attendance report retrieved successfully'));
    }
}
