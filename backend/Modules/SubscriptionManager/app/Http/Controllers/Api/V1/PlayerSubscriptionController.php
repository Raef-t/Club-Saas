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
        description: 'استرجاع قائمة بجميع اشتراكات الأعضاء في النادي. يمكن التصفية حسب الفرع.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية الاشتراكات حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
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
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $subscriptions = $this->subscriptionService->getAllSubscriptions($request->all());
        return $this->successResponse(
            PlayerSubscriptionResource::collection($subscriptions),
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
                new OA\Property(property: 'payment_method', type: 'string', example: 'cash', description: 'طريقة الدفع (اختياري)'),
                new OA\Property(property: 'receipt_number', type: 'string', example: 'REC-2026-001', description: 'رقم إيصال الدفع (اختياري)')
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
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
                new PlayerSubscriptionResource($subscription->load(['creator.person', 'plan.planActivities.staffActivity.activity', 'plan.planActivities.staffActivity.staff.person', 'items', 'payments', 'invoices.payments'])),
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الاشتراك', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        try {
            $subscription = $this->subscriptionService->getSubscriptionById($id);
            $subscription->load(['creator.person', 'plan.planActivities.staffActivity.activity', 'plan.planActivities.staffActivity.staff.person', 'items', 'freezes', 'payments', 'invoices.payments']);
            return $this->successResponse(
                new PlayerSubscriptionResource($subscription),
                __('Subscription retrieved successfully')
            );
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
                new OA\Property(property: 'reason', type: 'string', description: 'سبب التعديل (حقل إجباري قبل الحفظ)', example: 'تعديل تاريخ بداية ونهاية الاشتراك'),
                new OA\Property(property: 'member_id', type: 'integer', example: 1, description: 'معرف العضو (اختياري)'),
                new OA\Property(property: 'plan_id', type: 'integer', example: 1, description: 'معرف الخطة (اختياري)'),
                new OA\Property(property: 'offer_id', type: 'integer', example: 1, description: 'معرف العرض (اختياري)'),
                new OA\Property(property: 'months_count', type: 'integer', example: 1, description: 'عدد الأشهر (اختياري)'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-08-01', description: 'تاريخ بداية الاشتراك (اختياري)'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-09-01', description: 'تاريخ نهاية الاشتراك (اختياري)'),
                new OA\Property(property: 'status', type: 'string', example: 'active', description: 'حالة الاشتراك (اختياري)'),
                new OA\Property(property: 'paid_amount', type: 'number', format: 'float', example: 100.00, description: 'المبلغ المدفوع (اختياري)'),
                new OA\Property(property: 'payment_method', type: 'string', example: 'cash', description: 'طريقة الدفع (اختياري)'),
                new OA\Property(property: 'receipt_number', type: 'string', example: 'REC-2026-001', description: 'رقم إيصال الدفع (اختياري)'),
                new OA\Property(property: 'notes', type: 'string', example: 'ملاحظات معدلة', description: 'ملاحظات (اختياري)')
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
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
                new PlayerSubscriptionResource($subscription->load(['creator.person', 'plan.planActivities.staffActivity.activity', 'plan.planActivities.staffActivity.staff.person', 'items', 'payments', 'invoices.payments'])),
                __('Subscription updated successfully')
            );
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
            required: ['freeze_start_date'],
            properties: [
                new OA\Property(property: 'freeze_start_date', type: 'string', format: 'date', example: '2023-11-01'),
                new OA\Property(property: 'reason', type: 'string', example: 'السفر')
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
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
                $data['reason'] ?? null
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
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
    #[OA\Response(response: 200, description: '✅ تم حذف الاشتراك بنجاح')]
    #[OA\Response(response: 404, description: '🚫 الاشتراك غير موجود')]
    public function destroy(Request $request, int $id)
    {
        $subscription = \Modules\SubscriptionManager\Models\PlayerSubscription::findOrFail($id);

        $isRefunded = filter_var($request->input('is_refunded', $request->input('refunded', false)), FILTER_VALIDATE_BOOLEAN);
        $reason = $request->input('reason');

        if ($reason) {
            $subscription->update(['reason' => $reason]);
        }

        // If price was refunded to player, soft delete the revenue split record as well
        if ($isRefunded && $subscription->revenueSplit) {
            $subscription->revenueSplit->delete();
        }

        $subscription->delete();

        $message = $isRefunded 
            ? __('Player subscription deleted and revenue split record removed due to refund.') 
            : __('Player subscription deleted successfully and revenue split record preserved.');

        return $this->successResponse(null, $message);
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/restore',
        summary: '♻️ استرجاع اشتراك محذوف',
        description: 'استرجاع اشتراك المتدرب المحذوف ناعماً وكافّة تفاصيله وفواتيره وسجل كسر الإيرادات إن وُجد تلقائياً.',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الاشتراك وكافة سجلاته المالية المرفقة بنجاح')]
    #[OA\Response(response: 404, description: '🚫 الاشتراك غير موجود في سلة المحذوفات')]
    public function restore(int $id)
    {
        $subscription = \Modules\SubscriptionManager\Models\PlayerSubscription::onlyTrashed()->findOrFail($id);
        
        // Restore revenue split if it was soft-deleted
        $subscription->revenueSplit()->onlyTrashed()->restore();

        $subscription->restore();
        return $this->successResponse(null, __('Player subscription restored successfully'));
    }
}
