<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;
use Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionResource;
use Modules\SubscriptionManager\Services\SubscriptionService;
use Modules\SubscriptionManager\Http\Requests\SubscribeMemberRequest;
use Modules\SubscriptionManager\Http\Requests\UpdatePlayerSubscriptionRequest;
use Modules\SubscriptionManager\Http\Requests\FreezeSubscriptionRequest;
use Modules\SubscriptionManager\Http\Requests\RenewSubscriptionRequest;
use Modules\SubscriptionManager\Http\Requests\CancelSubscriptionRequest;
use Modules\SubscriptionManager\Http\Requests\RecordPaymentRequest;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Attributes as OA;

class PlayerSubscriptionController extends BaseController
{
    protected $subscriptionRepository;
    protected $subscriptionService;

    public function __construct(
        PlayerSubscriptionRepositoryInterface $subscriptionRepository,
        SubscriptionService $subscriptionService
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriptionService = $subscriptionService;
    }


    #[OA\Get(
        path: '/v1/player-subscriptions',
        summary: '👥 عرض اشتراكات الأعضاء',
        description: 'استرجاع قائمة بجميع اشتراكات الأعضاء في النادي مع الإحصائيات. يدعم التصفية حسب الفرع، نوع النشاط (الخاص، العام، الحصة الجماعية أو بالمعرف أو الاسم)، والبحث بالاسم أو رقم العضو، والفلترة حسب الحالة (فعال، تنتهي قريباً، منتهي، مجمد، تم إنهاؤه من الإدارة) وفترة التسجيل (اليوم، بالشهر، الكل).',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية الاشتراكات حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'activity_type', in: 'query', required: false, description: 'تصفية حسب نوع النشاط: الخاص (الاشتراكات الخاصة)، العام (الاشتراكات العامة)، الحصة الجماعية (الحصص الجماعية)، أو اسم نوع النشاط الرياضي أو معرفه', schema: new OA\Schema(type: 'string', example: 'الخاص'))]
    #[OA\Parameter(name: 'activity_type_id', in: 'query', required: false, description: 'تصفية حسب معرف نوع النشاط الرياضي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'search', in: 'query', required: false, description: 'بحث بالاسم، رقم العضو، اسم المستخدم، أو رقم الهاتف', schema: new OA\Schema(type: 'string', example: 'محمد'))]
    #[OA\Parameter(name: 'name', in: 'query', required: false, description: 'بحث باسم المشترك', schema: new OA\Schema(type: 'string', example: 'محمد'))]
    #[OA\Parameter(name: 'member_number', in: 'query', required: false, description: 'بحث برقم العضوية', schema: new OA\Schema(type: 'string', example: 'MEM-10023'))]
    #[OA\Parameter(name: 'username', in: 'query', required: false, description: 'بحث باسم المستخدم', schema: new OA\Schema(type: 'string', example: 'mohammed99'))]
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'تصفية حسب الحالة: active (فعال), expiring_soon (تنتهي قريباً), finished (منتهي), frozen (مجمد), terminated (تم إنهاؤه من الإدارة), all (الكل)', schema: new OA\Schema(type: 'string', enum: ['active', 'expiring_soon', 'finished', 'frozen', 'terminated', 'all'], example: 'active'))]
    #[OA\Parameter(name: 'period', in: 'query', required: false, description: 'تصفية حسب فترة التسجيل: today (تسجلت اليوم), monthly (الشهر الحالي), all (الكل)', schema: new OA\Schema(type: 'string', enum: ['today', 'monthly', 'all'], example: 'today'))]
    #[OA\Parameter(name: 'month', in: 'query', required: false, description: 'تصفية حسب شهر التسجيل (1-12 أو YYYY-MM)', schema: new OA\Schema(type: 'string', example: '2026-09'))]
    #[OA\Parameter(name: 'year', in: 'query', required: false, description: 'تصفية حسب سنة التسجيل', schema: new OA\Schema(type: 'integer', example: 2026))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الاشتراكات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscriptions retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(type: 'object')
                ),
                new OA\Property(
                    property: 'stats',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'active_subscriptions', type: 'integer', example: 45),
                        new OA\Property(property: 'total_subscriptions', type: 'integer', example: 120),
                        new OA\Property(property: 'total_paid_amount', type: 'number', format: 'float', example: 15400.00),
                        new OA\Property(property: 'today_revenue', type: 'number', format: 'float', example: 1200.00),
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $filters = $request->all();
        $subscriptions = $this->subscriptionService->getAllSubscriptions($filters);
        $stats = $this->subscriptionService->getSubscriptionStatistics($filters);

        if ($subscriptions instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $resourceCollection = PlayerSubscriptionResource::collection($subscriptions);
            $responseData = $resourceCollection->response()->getData(true);
            $responseData['stats'] = $stats;
            return $this->successResponse(
                $responseData,
                __('Subscriptions retrieved successfully')
            );
        }

        $resourceCollection = PlayerSubscriptionResource::collection($subscriptions);
        $responseData = [
            'data'  => $resourceCollection->resolve(),
            'stats' => $stats,
        ];
        return $this->successResponse(
            $responseData,
            __('Subscriptions retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions',
        summary: '➕ تسجيل اشتراك جديد لعضو',
        description: 'إنشاء اشتراك جديد لعضو محدد في خطة معينة.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id', 'plan_id', 'paid_amount'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 1),
                new OA\Property(property: 'plan_id', type: 'integer', example: 1),
                new OA\Property(property: 'months_count', type: 'integer', example: 1, description: 'عدد الأشهر للاشتراك (افتراضياً 1)'),
                new OA\Property(property: 'paid_amount', type: 'number', format: 'float', example: 50.00, description: 'المبلغ المدفوع فوراً (أدخل 0 إذا لم يتم الدفع)'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-07-01', description: 'تاريخ بداية الاشتراك (مطلوب)'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-08-01', description: 'تاريخ نهاية الاشتراك (اختياري، في حال عدم تمريره يتم حسابه تلقائياً من عدد الأشهر)'),
                new OA\Property(property: 'notes', type: 'string', example: 'ملاحظات إضافية', description: 'ملاحظات (اختياري)'),
                new OA\Property(property: 'receipt_number', type: 'string', example: 'REC-2026-001', description: 'رقم إيصال الدفع العام / النادي (اختياري)'),
                new OA\Property(property: 'coach_receipt_number', type: 'string', example: 'REC-COACH-001', description: 'رقم إيصال دفعة الكوتش للاشتراك الخاص (اختياري)'),
                new OA\Property(property: 'branch_receipt_number', type: 'string', example: 'REC-CLUB-001', description: 'رقم إيصال دفعة النادي للاشتراك الخاص (اختياري)'),
                new OA\Property(property: 'coach_paid_amount', type: 'number', format: 'float', example: 200.00, description: 'مبلغ دفعة الكوتش (اختياري)'),
                new OA\Property(property: 'branch_paid_amount', type: 'number', format: 'float', example: 100.00, description: 'مبلغ دفعة النادي (اختياري)')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الاشتراك بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member subscribed successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Member subscribed successfully',
                'data' => [
                    'id' => 1,
                    'subscription_number' => 'SUB-2026-001',
                    'member_id' => 12,
                    'plan_id' => 1,
                    'status' => 'active',
                    'start_date' => '2026-07-01',
                    'end_date' => '2026-08-01',
                    'total_amount' => 150.00,
                    'paid_amount' => 50.00,
                    'remaining_amount' => 100.00,
                    'notes' => 'ملاحظات إضافية'
                ]
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ خطأ في عملية الاشتراك (مثل الخطة مكتملة أو خطأ في الفرع)', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'This subscription plan has reached its maximum capacity.'), new OA\Property(property: 'data', type: 'null', example: null)]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(SubscribeMemberRequest $request)
    {
        try {
            $data = $request->validated();

            $subscription = $this->subscriptionService->subscribeMember(
                $data['member_id'],
                $data['plan_id'],
                $data
            );

            return $this->successResponse(
                new PlayerSubscriptionResource($subscription->load(['creator.person', 'plan.planActivities.staffActivity.activity', 'plan.planActivities.staffActivity.staff.person', 'items', 'payments', 'invoices.payments', 'revenueSplit'])),
                __('Member subscribed successfully'),
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Get(
        path: '/v1/player-subscriptions/{player_subscription}',
        summary: '🔍 تفاصيل الاشتراك',
        description: 'استرجاع تفاصيل اشتراك عضو محدد مع تجميداته.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'player_subscription', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل الاشتراك',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Subscription retrieved successfully',
                'data' => [
                    'id' => 1,
                    'subscription_number' => 'SUB-2026-001',
                    'member_id' => 12,
                    'plan_id' => 1,
                    'status' => 'active',
                    'start_date' => '2026-07-01',
                    'end_date' => '2026-08-01',
                    'total_amount' => 150.00,
                    'paid_amount' => 150.00,
                    'remaining_amount' => 0.00,
                    'freezes' => [
                        [
                            'id' => 1,
                            'player_subscription_id' => 1,
                            'freeze_start_date' => '2026-09-02',
                            'freeze_end_date' => '2026-09-09',
                            'actual_end_date' => '2026-09-07',
                            'reason' => 'إجازة وسفر',
                            'freeze_days' => 5
                        ]
                    ],
                    'payments' => []
                ]
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الاشتراك', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        try {
            $subscription = $this->subscriptionService->getSubscriptionById($id);
            $subscription->load(['creator.person', 'plan.planActivities.staffActivity.activity', 'plan.planActivities.staffActivity.staff.person', 'items', 'freezes', 'payments', 'invoices.payments', 'revenueSplit']);
            return $this->successResponse(
                new PlayerSubscriptionResource($subscription),
                __('Subscription retrieved successfully')
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('Record not found.'), 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Put(
        path: '/v1/player-subscriptions/{id}',
        summary: '✏️ تعديل بيانات اشتراك عضو',
        description: 'تعديل بيانات اشتراك عضو محدد كالتاريخ، الخطة، الملاحظات، الحالة أو المبالغ المالية.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['reason'],
            properties: [
                new OA\Property(property: 'reason', type: 'string', description: 'سبب التعديل (حقل إجباري لتتبع التعديلات والرقابة)', example: 'تعديل تاريخ بداية ونهاية الاشتراك ومبالغ وإيصالات الكوتش والفرع'),
                new OA\Property(property: 'member_id', type: 'integer', nullable: true, example: 47, description: 'معرف العضو (اختياري)'),
                new OA\Property(property: 'plan_id', type: 'integer', nullable: true, example: 83, description: 'معرف الخطة (اختياري)'),
                new OA\Property(property: 'offer_id', type: 'integer', nullable: true, example: 1, description: 'معرف العرض (اختياري)'),
                new OA\Property(property: 'months_count', type: 'integer', nullable: true, example: 1, description: 'عدد الأشهر للاشتراك (اختياري)'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-10-01', description: 'تاريخ بداية الاشتراك YYYY-MM-DD (اختياري)'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2026-11-01', description: 'تاريخ نهاية الاشتراك YYYY-MM-DD (اختياري)'),
                new OA\Property(property: 'status', type: 'string', enum: ['active', 'finished', 'frozen', 'terminated', 'expired', 'cancelled'], nullable: true, example: 'active', description: 'حالة الاشتراك (اختياري)'),
                new OA\Property(property: 'paid_amount', type: 'number', format: 'float', nullable: true, example: 350.00, description: 'إجمالي المبلغ المدفوع (اختياري)'),
                new OA\Property(property: 'coach_paid_amount', type: 'number', format: 'float', nullable: true, example: 200.00, description: 'مبلغ دفعة الكوتش للاشتراك الخاص (اختياري)'),
                new OA\Property(property: 'branch_paid_amount', type: 'number', format: 'float', nullable: true, example: 150.00, description: 'مبلغ دفعة النادي/الفرع للاشتراك الخاص (اختياري)'),
                new OA\Property(property: 'coach_price', type: 'number', format: 'float', nullable: true, example: 200.00, description: 'سعر الكوتش (اسم بديل لـ coach_paid_amount)'),
                new OA\Property(property: 'branch_price', type: 'number', format: 'float', nullable: true, example: 150.00, description: 'سعر الفرع (اسم بديل لـ branch_paid_amount)'),
                new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'card', 'wallet', 'bank_transfer'], nullable: true, example: 'cash', description: 'طريقة الدفع (اختياري)'),
                new OA\Property(property: 'receipt_number', type: 'string', nullable: true, example: 'REC-CLUB-004', description: 'رقم إيصال الدفع العام (اختياري)'),
                new OA\Property(property: 'coach_receipt_number', type: 'string', nullable: true, example: 'REC-COACH-005', description: 'رقم إيصال دفعة الكوتش للاشتراك الخاص (اختياري)'),
                new OA\Property(property: 'branch_receipt_number', type: 'string', nullable: true, example: 'REC-CLUB-006', description: 'رقم إيصال دفعة النادي/الفرع للاشتراك الخاص (اختياري)'),
                new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'ملاحظات إضافية معدلة', description: 'ملاحظات (اختياري)')
            ],
            example: [
                'reason' => 'تعديل تاريخ بداية ونهاية الاشتراك ومبالغ وإيصالات الكوتش والفرع',
                'start_date' => '2026-10-01',
                'end_date' => '2026-11-01',
                'months_count' => 1,
                'status' => 'active',
                'paid_amount' => 350.00,
                'coach_paid_amount' => 200.00,
                'branch_paid_amount' => 150.00,
                'payment_method' => 'cash',
                'coach_receipt_number' => 'REC-COACH-005',
                'branch_receipt_number' => 'REC-CLUB-006',
                'notes' => 'ملاحظات إضافية معدلة'
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تعديل الاشتراك بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Subscription updated successfully',
                'data' => [
                    'id' => 1,
                    'subscription_number' => 'SUB-2026-001',
                    'start_date' => '2026-08-01',
                    'end_date' => '2026-09-01',
                    'status' => 'active',
                    'reason' => 'تعديل تاريخ بداية ونهاية الاشتراك'
                ]
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ خطأ في عملية التعديل', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Subscription update failed.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdatePlayerSubscriptionRequest $request, $id)
    {
        try {
            $data = array_filter($request->validated(), fn ($val) => !is_null($val));
            $subscription = $this->subscriptionService->updateSubscription((int) $id, $data);

            return $this->successResponse(
                new PlayerSubscriptionResource($subscription->load(['creator.person', 'plan.planActivities.staffActivity.activity', 'plan.planActivities.staffActivity.staff.person', 'items', 'payments', 'invoices.payments', 'revenueSplit'])),
                __('Subscription updated successfully')
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('Record not found.'), 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/freeze',
        summary: '❄️ تجميد الاشتراك',
        description: 'إيقاف الاشتراك مؤقتاً لعضو.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['freeze_start_date', 'reason'],
            properties: [
                new OA\Property(property: 'freeze_start_date', type: 'string', format: 'date', example: '2026-09-07', description: 'تاريخ بدء التجميد (YYYY-MM-DD)'),
                new OA\Property(property: 'freeze_end_date', type: 'string', format: 'date', nullable: true, example: '2026-09-14', description: 'تاريخ نهاية التجميد المتوقع (اختياري - YYYY-MM-DD)'),
                new OA\Property(property: 'days_count', type: 'integer', nullable: true, example: 7, description: 'عدد أيام التجميد (اختياري، لحساب تاريخ النهاية تلقائياً)'),
                new OA\Property(property: 'reason', type: 'string', maxLength: 500, example: 'إجازة وسفر لمدة أسبوع', description: 'سبب التجميد (إجباري)')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تجميد الاشتراك بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription frozen successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Subscription frozen successfully',
                'data' => [
                    'id' => 1,
                    'status' => 'frozen',
                    'status_label' => 'مجمّد',
                    'start_date' => '2026-09-01',
                    'end_date' => '2026-10-08',
                    'plan' => [
                        'id' => 1,
                        'name' => 'اشتراك فتنس شهري'
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ خطأ في العملية (مثل: التجميد غير مسموح في هذا الفرع)', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Freezing is not allowed in this branch.'), new OA\Property(property: 'data', type: 'null', example: null)]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function freeze(FreezeSubscriptionRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $subscription = $this->subscriptionService->freezeSubscription(
                $id,
                $data['freeze_start_date'],
                $data['reason'],
                $data['freeze_end_date'] ?? null
            );

            return $this->successResponse(
                new PlayerSubscriptionResource($subscription->load(['plan'])),
                __('Subscription frozen successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/unfreeze',
        summary: '🔓 إلغاء تجميد الاشتراك',
        description: 'إعادة تفعيل الاشتراك بعد تجميده.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم إلغاء تجميد الاشتراك بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription unfrozen successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Subscription unfrozen successfully',
                'data' => [
                    'id' => 1,
                    'status' => 'active'
                ]
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ خطأ في عملية إلغاء التجميد (مثل الاشتراك ليس مجمداً)', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Subscription is not frozen.'), new OA\Property(property: 'data', type: 'null', example: null)]))]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الاشتراك', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function unfreeze(int $id)
    {
        try {
            $subscription = $this->subscriptionService->unfreezeSubscription($id);
            return $this->successResponse(
                new PlayerSubscriptionResource($subscription->load(['plan'])),
                __('Subscription unfrozen successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/renew',
        summary: '🔄 تجديد الاشتراك',
        description: 'تجديد اشتراك العضو في نفس الخطة أو خطة جديدة.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك الحالي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'plan_id', type: 'integer', example: 1, description: 'معرف الخطة المراد التجديد عليها (اختياري)'),
                new OA\Property(property: 'paid_amount', type: 'number', format: 'float', example: 50.00, description: 'المبلغ المدفوع (اختياري)'),
                new OA\Property(property: 'payment_method', type: 'string', example: 'cash', description: 'طريقة الدفع (اختياري)'),
                new OA\Property(property: 'receipt_number', type: 'string', example: 'REC-2026-002', description: 'رقم إيصال الدفع (اختياري)'),
                new OA\Property(property: 'coach_id', type: 'integer', example: 1, description: 'معرف المدرب (اختياري)'),
                new OA\Property(property: 'notes', type: 'string', example: 'ملاحظات التجديد', description: 'ملاحظات (اختياري)')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم التجديد بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription renewed successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Subscription renewed successfully',
                'data' => [
                    'id' => 2,
                    'subscription_number' => 'SUB-2026-002',
                    'member_id' => 12,
                    'plan_id' => 1,
                    'status' => 'active',
                    'start_date' => '2026-08-01',
                    'end_date' => '2026-09-01',
                    'total_amount' => 150.00,
                    'paid_amount' => 50.00
                ]
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ خطأ في عملية التجديد', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Invalid subscription renewal request.'), new OA\Property(property: 'data', type: 'null', example: null)]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function renew(RenewSubscriptionRequest $request, int $id)
    {
        try {
            $options = $request->validated();

            $subscription = $this->subscriptionService->renewSubscription($id, $options);
            return $this->successResponse(
                new PlayerSubscriptionResource($subscription->load(['plan', 'payments', 'invoices.payments'])),
                __('Subscription renewed successfully'),
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/cancel',
        summary: '❌ إلغاء الاشتراك',
        description: 'إنهاء اشتراك عضو قبل موعد انتهائه.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'بناءً على طلب العضو')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم إلغاء الاشتراك بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription cancelled successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Subscription cancelled successfully',
                'data' => [
                    'id' => 1,
                    'status' => 'cancelled'
                ]
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ خطأ في عملية الإلغاء (مثل الاشتراك ملغى مسبقاً)', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Subscription is already cancelled.'), new OA\Property(property: 'data', type: 'null', example: null)]))]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الاشتراك', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function cancel(CancelSubscriptionRequest $request, int $id)
    {
        try {
            $data = $request->validated();

            $subscription = $this->subscriptionService->cancelSubscription($id, $data['reason'] ?? null);
            return $this->successResponse(
                new PlayerSubscriptionResource($subscription),
                __('Subscription cancelled successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/payment',
        summary: '💳 تسجيل دفعة مالية',
        description: 'تسجيل دفعة مالية جديدة على اشتراك العضو.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['amount'],
            properties: [
                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150.00, description: 'قيمة الدفعة المالية'),
                new OA\Property(property: 'payment_method', type: 'string', example: 'cash', description: 'طريقة الدفع (اختياري)'),
                new OA\Property(property: 'receipt_number', type: 'string', example: 'REC-2026-003', description: 'رقم إيصال الدفع (اختياري)')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تسجيل الدفعة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Payment recorded successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Payment recorded successfully',
                'data' => [
                    'id' => 1,
                    'total_amount' => 150.00,
                    'paid_amount' => 150.00,
                    'remaining_amount' => 0.00
                ]
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ خطأ في تسجيل الدفعة المالية', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Payment error.'), new OA\Property(property: 'data', type: 'null', example: null)]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function recordPayment(RecordPaymentRequest $request, int $id)
    {
        try {
            $data = $request->validated();

            $subscription = $this->subscriptionService->recordPayment($id, (float) $data['amount'], $data);
            return $this->successResponse(
                new PlayerSubscriptionResource($subscription->load(['plan', 'payments', 'invoices.payments'])),
                __('Payment recorded successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Delete(
        path: '/v1/player-subscriptions/{id}',
        summary: '🗑️ حذف اشتراك متدرب (Soft Delete)',
        description: 'حذف اشتراك المتدرب ناعماً. يقبل معامل is_refunded لتحديد ما إذا تمت إعادة سعر الاشتراك للاعب (إذا نعم: يُحذف سجل الكسر المالي subscription_revenue_splits، إذا لا: يبقى السجل المالي محفوظاً).',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'is_refunded', in: 'query', required: false, description: 'هل تمت إعادة سعر الاشتراك للاعب؟ (true / false)', schema: new OA\Schema(type: 'boolean', example: true))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'is_refunded', type: 'boolean', example: true, description: 'هل تمت إعادة سعر الاشتراك للاعب؟ (إذا نعم يتم حذف سجل الكسر المالي، إذا لا يبقى محفوظاً)'),
                new OA\Property(property: 'reason', type: 'string', example: 'طلب اللاعب إلغاء واسترداد المبلغ', description: 'سبب الحذف (اختياري)')
            ]
        )
    )]
    #[OA\Response(
        response: 200, 
        description: '✅ تم حذف الاشتراك بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Player subscription deleted successfully and revenue split record preserved.')
            ],
            example: [
                'status' => 'success',
                'message' => 'Player subscription deleted successfully and revenue split record preserved.'
            ]
        )
    )]
    #[OA\Response(
        response: 404, 
        description: '🚫 الاشتراك غير موجود',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Record not found.')
            ]
        )
    )]
    public function destroy(Request $request, int $id)
    {
        try {
            $isRefunded = filter_var($request->input('is_refunded', $request->input('refunded', false)), FILTER_VALIDATE_BOOLEAN);
            $reason = $request->input('reason');

            $this->subscriptionService->deleteSubscription($id, $isRefunded, $reason);

            $message = $isRefunded 
                ? __('Player subscription deleted and revenue split record removed due to refund.') 
                : __('Player subscription deleted successfully and revenue split record preserved.');

            return $this->successResponse(null, $message);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('Record not found.'), 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/restore',
        summary: '♻️ استرجاع اشتراك محذوف',
        description: 'استرجاع اشتراك المتدرب المحذوف ناعماً وكافّة تفاصيله وفواتيره وسجل كسر الإيرادات إن وُجد تلقائياً.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200, 
        description: '✅ تم استرجاع الاشتراك وكافة سجلاته المالية المرفقة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Player subscription restored successfully')
            ],
            example: [
                'status' => 'success',
                'message' => 'Player subscription restored successfully'
            ]
        )
    )]
    #[OA\Response(
        response: 404, 
        description: '🚫 الاشتراك غير موجود في سلة المحذوفات',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Record not found.')
            ]
        )
    )]
    public function restore(int $id)
    {
        try {
            $this->subscriptionService->restoreSubscription($id);
            return $this->successResponse(null, __('Player subscription restored successfully'));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('Record not found.'), 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
