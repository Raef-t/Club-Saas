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
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: 'تصفية بحالة الاشتراك: active (فعال), finished (منتهي), frozen (مجمد), terminated (ملغى), all (الجميع)',
        schema: new OA\Schema(type: 'string', enum: ['all', 'active', 'finished', 'frozen', 'terminated'], default: 'all')
    )]
    #[OA\Parameter(
        name: 'plan_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب خطة الاشتراك',
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\Parameter(
        name: 'payment_status',
        in: 'query',
        required: false,
        description: 'تصفية بحالة الدفع: paid (مدفوع بالكامل), partially_paid (مدفوع جزئياً), unpaid (غير مدفوع), all (الجميع)',
        schema: new OA\Schema(type: 'string', enum: ['all', 'paid', 'partially_paid', 'unpaid'], default: 'all')
    )]
    #[OA\Parameter(
        name: 'coach_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب الكوتش المسند',
        schema: new OA\Schema(type: 'integer', example: 2)
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب الفرع',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'start_date',
        in: 'query',
        required: false,
        description: 'تاريخ بداية النطاق الزمني (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01')
    )]
    #[OA\Parameter(
        name: 'end_date',
        in: 'query',
        required: false,
        description: 'تاريخ نهاية النطاق الزمني (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-31')
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        required: false,
        description: 'بحث باسم اللاعب، رقم الهاتف، أو رقم العضوية',
        schema: new OA\Schema(type: 'string', example: 'أحمد')
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تقرير الاشتراكات الشامل بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'All subscriptions report retrieved successfully',
                'data' => [
                    'summary' => [
                        'total_subscriptions' => 120,
                        'total_revenue' => 15000.0,
                        'total_paid' => 12000.0,
                        'total_remaining' => 3000.0,
                        'active_count' => 80,
                        'finished_count' => 25,
                        'frozen_count' => 10,
                        'terminated_count' => 5,
                        'fully_paid_count' => 95,
                        'partially_paid_count' => 15,
                        'unpaid_count' => 10
                    ],
                    'records' => [
                        [
                            'subscription_id' => 101,
                            'member' => [
                                'id' => 10,
                                'member_number' => 'MEM-10023',
                                'name' => 'أحمد محمود',
                                'phone' => '0501234567',
                                'branch_name' => 'الفرع الرئيسي'
                            ],
                            'plan' => [
                                'id' => 5,
                                'name' => 'الاشتراك الذهبي الشامل',
                                'type' => 'monthly'
                            ],
                            'status' => 'active',
                            'payment_status' => 'paid',
                            'total_amount' => 500.0,
                            'paid_amount' => 500.0,
                            'remaining_amount' => 0.0,
                            'start_date' => '2026-07-01',
                            'end_date' => '2026-08-01',
                            'coaches' => ['الكابتن طارق علي'],
                            'sessions' => [
                                'total_sessions' => 12,
                                'attended_sessions' => 4,
                                'remaining_sessions' => 8
                            ]
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح - رمز المرور مفقود أو غير صالح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
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
    #[OA\Parameter(
        name: 'type',
        in: 'query',
        required: false,
        description: 'نوع التقرير: expired_non_renewed (منتهي ولم يجدد), renewed (تم التجديد), all (الجميع)',
        schema: new OA\Schema(type: 'string', enum: ['expired_non_renewed', 'renewed', 'all'], default: 'all')
    )]
    #[OA\Parameter(
        name: 'date_filter_by',
        in: 'query',
        required: false,
        description: 'المعيار الزمني للتصفية: end_date (انقضاء الاشتراك), start_date (بداية الاشتراك), created_date (تاريخ القيد)',
        schema: new OA\Schema(type: 'string', enum: ['end_date', 'start_date', 'created_date'], default: 'end_date')
    )]
    #[OA\Parameter(
        name: 'start_date',
        in: 'query',
        required: false,
        description: 'تاريخ بداية النطاق الزمني',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01')
    )]
    #[OA\Parameter(
        name: 'end_date',
        in: 'query',
        required: false,
        description: 'تاريخ نهاية النطاق الزمني',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-31')
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب الفرع',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'plan_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب خطة الاشتراك',
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\Parameter(
        name: 'coach_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب المدرب المسند',
        schema: new OA\Schema(type: 'integer', example: 2)
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        required: false,
        description: 'بحث برقم الهاتف، اسم اللاعب، أو رقم العضوية',
        schema: new OA\Schema(type: 'string', example: 'أحمد')
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تقرير التجديد والانقضاء بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Subscription renewal status report retrieved successfully',
                'data' => [
                    'summary' => [
                        'total_records' => 25,
                        'total_expired_non_renewed' => 15,
                        'total_renewed' => 10,
                        'renewal_rate_percentage' => 40.0,
                        'total_lost_potential_revenue' => 2250.0,
                        'total_renewed_revenue' => 1800.0
                    ],
                    'records' => [
                        [
                            'subscription_id' => 101,
                            'status_type' => 'expired_non_renewed',
                            'status_label' => 'منتهي ولم يجدد',
                            'member_id' => 12,
                            'member_number' => 'MEM-10023',
                            'member_name' => 'محمد أحمد',
                            'member_phone' => '0501234567',
                            'contact_persons' => [
                                [
                                    'id' => 1,
                                    'name' => 'أبو محمد',
                                    'phone_number' => '0501234567',
                                    'relation' => 'أب'
                                ]
                            ],
                            'absence_period' => [
                                'last_attendance_date' => '2026-06-15 17:30:00',
                                'years' => 0,
                                'months' => 1,
                                'days' => 16,
                                'total_days' => 46,
                                'formatted' => '0 سنة، 1 شهر، 16 يوم'
                            ],
                            'branch_name' => 'الفرع الرئيسي',
                            'plan_id' => 5,
                            'plan_name' => 'اشتراك لياقة شهري',
                            'plan_type' => 'monthly',
                            'coaches_names' => 'الكابتن طارق علي',
                            'start_date' => '2026-06-01',
                            'end_date' => '2026-07-01',
                            'subscription_status' => 'expired',
                            'days_since_expiration' => 28,
                            'total_amount' => 150.0,
                            'paid_amount' => 150.0,
                            'remaining_amount' => 0.0,
                            'is_fully_paid' => true
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
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
    #[OA\Parameter(
        name: 'start_time',
        in: 'query',
        required: false,
        description: 'وقت البداية لتصفية الحصص (مثل 10:00:00)',
        schema: new OA\Schema(type: 'string', example: '10:00:00')
    )]
    #[OA\Parameter(
        name: 'end_time',
        in: 'query',
        required: false,
        description: 'وقت النهاية لتصفية الحصص (مثل 14:00:00)',
        schema: new OA\Schema(type: 'string', example: '14:00:00')
    )]
    #[OA\Parameter(
        name: 'day_of_week',
        in: 'query',
        required: false,
        description: 'يوم الأسبوع (0=الأحد، 1=الاثنين ... 6=السبت)',
        schema: new OA\Schema(type: 'integer', minimum: 0, maximum: 6, example: 0)
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب الفرع',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'plan_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب خطة محددة',
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\Parameter(
        name: 'activity_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب نشاط رياضي محدد',
        schema: new OA\Schema(type: 'integer', example: 2)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تقرير سعة الحصص بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Time-slot capacity report retrieved successfully',
                'data' => [
                    'summary' => [
                        'total_activities' => 3,
                        'total_coaches' => 5,
                        'total_plans' => 8,
                        'total_active_subscribers' => 45
                    ],
                    'activities' => [
                        [
                            'activity_id' => 1,
                            'activity_name' => 'كرة قدم',
                            'coaches' => [
                                [
                                    'staff_id' => 2,
                                    'coach_name' => 'الكابتن أحمد علي',
                                    'staff_activity_id' => 5,
                                    'plans' => [
                                        [
                                            'plan_id' => 10,
                                            'plan_name' => 'اشتراك كرة قدم - المستوى الأول',
                                            'plan_type' => 'monthly',
                                            'active_subscribers_count' => 15,
                                            'schedules' => [
                                                [
                                                    'session_template_id' => 101,
                                                    'day_of_week' => 0,
                                                    'day_name' => 'الأحد',
                                                    'start_time' => '16:00:00',
                                                    'end_time' => '17:30:00',
                                                    'facility_name' => 'الملعب الرئيسي'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
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
    #[OA\Parameter(
        name: 'start_date',
        in: 'query',
        required: false,
        description: 'تاريخ بداية التحليل (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01')
    )]
    #[OA\Parameter(
        name: 'end_date',
        in: 'query',
        required: false,
        description: 'تاريخ نهاية التحليل (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-31')
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب الفرع',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'attendable_type',
        in: 'query',
        required: false,
        description: 'تصفية بين الأعضاء (member) أو الكباتن (staff)',
        schema: new OA\Schema(type: 'string', enum: ['member', 'staff'], default: 'member')
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تقرير الذروة بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Peak hours report retrieved successfully',
                'data' => [
                    'summary' => [
                        'busiest_day' => 'الاثنين',
                        'quietest_day' => 'الجمعة',
                        'peak_hours_range' => '18:00',
                        'off_peak_hours_range' => '13:00',
                        'total_attendances_analyzed' => 1450,
                        'excluded_holidays_count' => 2
                    ],
                    'top_peak_hours' => [
                        [
                            'hour' => '18:00',
                            'label' => '06:00 PM - 07:00 PM',
                            'attendance_count' => 320
                        ]
                    ],
                    'top_off_peak_hours' => [
                        [
                            'hour' => '13:00',
                            'label' => '01:00 PM - 02:00 PM',
                            'attendance_count' => 25
                        ]
                    ],
                    'hourly_breakdown' => [
                        [
                            'hour' => '08:00',
                            'count' => 45
                        ]
                    ],
                    'daily_breakdown' => [
                        [
                            'day' => 'الاثنين',
                            'count' => 310
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
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
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: 'تصفية حسب الحالة: frozen (المجمدة فقط), terminated (الملغاة فقط), all (الجميع)',
        schema: new OA\Schema(type: 'string', enum: ['all', 'frozen', 'terminated'], default: 'all')
    )]
    #[OA\Parameter(
        name: 'date_filter_by',
        in: 'query',
        required: false,
        description: 'المعيار الزمني للتصفية: event_date (تاريخ حدوث التجميد/الإلغاء), start_date (بداية الاشتراك), end_date (نهاية الاشتراك), created_date (تاريخ القيد)',
        schema: new OA\Schema(type: 'string', enum: ['event_date', 'start_date', 'end_date', 'created_date'], default: 'event_date')
    )]
    #[OA\Parameter(
        name: 'start_date',
        in: 'query',
        required: false,
        description: 'تاريخ بداية النطاق الزمني (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01')
    )]
    #[OA\Parameter(
        name: 'end_date',
        in: 'query',
        required: false,
        description: 'تاريخ نهاية النطاق الزمني (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-31')
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب الفرع',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'plan_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب خطة الاشتراك',
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        required: false,
        description: 'بحث برقم الهاتف، اسم اللاعب، أو رقم العضوية',
        schema: new OA\Schema(type: 'string', example: 'أحمد')
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع التقرير بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Frozen and terminated subscriptions report retrieved successfully',
                'data' => [
                    'summary' => [
                        'total_records' => 15,
                        'total_frozen' => 10,
                        'total_terminated' => 5,
                        'total_frozen_revenue' => 5000.0,
                        'total_lost_terminated_revenue' => 2500.0
                    ],
                    'records' => [
                        [
                            'subscription_id' => 105,
                            'status' => 'frozen',
                            'status_label' => 'مجمد',
                            'member_name' => 'سارة علي',
                            'member_number' => 'MEM-10045',
                            'member_phone' => '0509876543',
                            'plan_name' => 'اشتراك ثلاثي الأشهر',
                            'branch_name' => 'فرع الرياض',
                            'event_date' => '2026-07-10',
                            'reason' => 'سفر مؤقت للخارج',
                            'frozen_days' => 14,
                            'unfreeze_date' => '2026-07-24',
                            'total_amount' => 600.0,
                            'paid_amount' => 600.0
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
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
    #[OA\Parameter(
        name: 'date',
        in: 'query',
        required: false,
        description: 'تصفية ليوم محدد (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-30')
    )]
    #[OA\Parameter(
        name: 'month',
        in: 'query',
        required: false,
        description: 'تصفية لشهر محدد (YYYY-MM)',
        schema: new OA\Schema(type: 'string', example: '2026-07')
    )]
    #[OA\Parameter(
        name: 'start_date',
        in: 'query',
        required: false,
        description: 'تاريخ بداية النطاق الزمني (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01')
    )]
    #[OA\Parameter(
        name: 'end_date',
        in: 'query',
        required: false,
        description: 'تاريخ نهاية النطاق الزمني (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-31')
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب الفرع',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'activity_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب النشاط المتبع للورديات',
        schema: new OA\Schema(type: 'integer', example: 3)
    )]
    #[OA\Parameter(
        name: 'shift_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب وردية محددة',
        schema: new OA\Schema(type: 'integer', example: 2)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تقرير حضور الورديات بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Shift attendance report retrieved successfully',
                'data' => [
                    'summary' => [
                        'period_label' => 'من 2026-07-01 إلى 2026-07-31',
                        'total_shift_attendances' => 450,
                        'total_shifts_count' => 6,
                        'busiest_shift' => [
                            'shift_id' => 2,
                            'shift_name' => 'الوردية المسائية',
                            'branch_name' => 'الفرع الرئيسي',
                            'attended_players_count' => 180,
                            'crowd_percentage' => '40%'
                        ],
                        'quietest_shift' => [
                            'shift_id' => 1,
                            'shift_name' => 'الوردية الصباحية',
                            'branch_name' => 'الفرع الرئيسي',
                            'attended_players_count' => 40,
                            'crowd_percentage' => '8.89%'
                        ]
                    ],
                    'records' => [
                        [
                            'shift_id' => 2,
                            'shift_name' => 'الوردية المسائية',
                            'start_time' => '16:00:00',
                            'end_time' => '20:00:00',
                            'branch_name' => 'الفرع الرئيسي',
                            'activity_name' => 'كرة قدم',
                            'attended_players_count' => 180,
                            'crowd_percentage' => '40%'
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function shiftAttendanceReport(Request $request)
    {
        $filters = $request->only(['date', 'month', 'start_date', 'end_date', 'branch_id', 'activity_id', 'shift_id']);
        $reportData = $this->reportService->getShiftAttendanceReport($filters);

        return $this->successResponse($reportData, __('Shift attendance report retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/reports/coaches/subscriptions',
        summary: '🏃‍♂️ تقرير كوتشات الحصص الجماعية والأجهزة العامة (Group Session Coaches & General Equipment Report)',
        description: 'استرجاع تقرير تفصيلي يُظهر كوتشات الحصص الجماعية والأنشطة التي يدربونها مع عدد اللاعبين النشطين المسجلين في كافة خططهم، بالإضافة إلى قسم خاص بأجهزة تدريب عام وإجمالي لاعبيها النشطين.',
        tags: ['Reports'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية حسب الفرع',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع التقرير بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Coach group sessions & general equipment report retrieved successfully',
                'data' => [
                    'summary' => [
                        'total_group_coaches' => 3,
                        'total_group_active_players' => 65,
                        'general_equipment_active_players' => 140
                    ],
                    'group_session_coaches' => [
                        [
                            'coach_id' => 1,
                            'coach_name' => 'الكابتن أحمد علي',
                            'activities' => ['كرة القدم', 'السباحة'],
                            'active_players_count' => 35
                        ]
                    ],
                    'general_equipment' => [
                        'title' => 'أجهزة عام',
                        'activity_type_name' => 'تدريب عام',
                        'active_players_count' => 140
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function coachSubscriptionReport(Request $request)
    {
        $filters = $request->only(['branch_id']);
        $reportData = $this->reportService->getCoachSubscriptionReport($filters);

        return $this->successResponse($reportData, __('Coach group sessions & general equipment report retrieved successfully'));
    }
}
