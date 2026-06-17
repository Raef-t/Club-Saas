<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;
use Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionResource;
use Modules\SubscriptionManager\Services\SubscriptionService;
use Modules\SubscriptionManager\Http\Requests\SubscribeMemberRequest;
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
        description: 'استرجاع قائمة بجميع اشتراكات الأعضاء في النادي.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الاشتراكات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscriptions retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $subscriptions = $this->subscriptionService->getAllSubscriptions($request->all());
        return $this->successResponse(
            PlayerSubscriptionResource::collection($subscriptions)->response()->getData(true),
            __('Subscriptions retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions',
        summary: '➕ تسجيل اشتراك جديد لعضو',
        description: 'إنشاء اشتراك جديد لعضو محدد في خطة معينة.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id', 'plan_id'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 1),
                new OA\Property(property: 'plan_id', type: 'integer', example: 1)
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
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(SubscribeMemberRequest $request)
    {
        $data = $request->validated();
        $subscription = $this->subscriptionService->subscribeMember(
            $data['member_id'],
            $data['plan_id'],
            $data
        );

        return $this->successResponse(
            new PlayerSubscriptionResource($subscription->load(['plan'])),
            __('Member subscribed successfully'),
            201
        );
    }

    #[OA\Get(
        path: '/v1/player-subscriptions/{id}',
        summary: '🔍 تفاصيل الاشتراك',
        description: 'استرجاع تفاصيل اشتراك عضو محدد مع تجميداته.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل الاشتراك',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الاشتراك', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $subscription = $this->subscriptionService->getSubscriptionById($id);
        $subscription->load(['plan', 'items', 'freezes']);
        return $this->successResponse(
            new PlayerSubscriptionResource($subscription),
            __('Subscription retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/freeze',
        summary: '❄️ تجميد الاشتراك',
        description: 'إيقاف الاشتراك مؤقتاً لعضو.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['freeze_start_date', 'freeze_end_date'],
            properties: [
                new OA\Property(property: 'freeze_start_date', type: 'string', format: 'date', example: '2023-11-01'),
                new OA\Property(property: 'freeze_end_date', type: 'string', format: 'date', example: '2023-11-15'),
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
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function freeze(FreezeSubscriptionRequest $request, $id)
    {
        $data = $request->validated();
        $subscription = $this->subscriptionService->freezeSubscription(
            $id,
            $data['freeze_start_date'],
            $data['freeze_end_date'],
            $data['reason'] ?? null
        );

        return $this->successResponse(
            new PlayerSubscriptionResource($subscription->load(['plan'])),
            __('Subscription frozen successfully')
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/unfreeze',
        summary: '🔓 إلغاء تجميد الاشتراك',
        description: 'إعادة تفعيل الاشتراك بعد تجميده.',
        tags: ['Subscription Management'],
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
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الاشتراك', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function unfreeze(int $id)
    {
        $subscription = $this->subscriptionService->unfreezeSubscription($id);
        return $this->successResponse(
            new PlayerSubscriptionResource($subscription->load(['plan'])),
            __('Subscription unfrozen successfully')
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/renew',
        summary: '🔄 تجديد الاشتراك',
        description: 'تجديد اشتراك العضو في نفس الخطة أو خطة جديدة.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك الحالي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'plan_id', type: 'integer', example: 1, description: 'معرف الخطة المراد التجديد عليها (اختياري)')
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
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function renew(RenewSubscriptionRequest $request, int $id)
    {
        $options = $request->validated();

        $subscription = $this->subscriptionService->renewSubscription($id, $options);
        return $this->successResponse(
            new PlayerSubscriptionResource($subscription->load(['plan'])),
            __('Subscription renewed successfully'),
            201
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/cancel',
        summary: '❌ إلغاء الاشتراك',
        description: 'إنهاء اشتراك عضو قبل موعد انتهائه.',
        tags: ['Subscription Management'],
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
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الاشتراك', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function cancel(CancelSubscriptionRequest $request, int $id)
    {
        $data = $request->validated();

        $subscription = $this->subscriptionService->cancelSubscription($id, $data['reason'] ?? null);
        return $this->successResponse(
            new PlayerSubscriptionResource($subscription),
            __('Subscription cancelled successfully')
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/payment',
        summary: '💳 تسجيل دفعة مالية',
        description: 'تسجيل دفعة مالية جديدة على اشتراك العضو.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الاشتراك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['amount'],
            properties: [
                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150.00)
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
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function recordPayment(RecordPaymentRequest $request, int $id)
    {
        $data = $request->validated();

        $subscription = $this->subscriptionService->recordPayment($id, $data['amount']);
        return $this->successResponse(
            new PlayerSubscriptionResource($subscription),
            __('Payment recorded successfully')
        );
    }
}
