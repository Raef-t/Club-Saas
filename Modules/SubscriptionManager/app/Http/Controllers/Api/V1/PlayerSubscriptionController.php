<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;
use Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionResource;
use Modules\SubscriptionManager\Services\SubscriptionService;
use Modules\SubscriptionManager\Http\Requests\SubscribeMemberRequest;
use Modules\SubscriptionManager\Http\Requests\FreezeSubscriptionRequest;
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
        summary: '👥 List all player subscriptions',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        $subscriptions = $this->subscriptionService->getAllSubscriptions();
        return $this->successResponse(
            PlayerSubscriptionResource::collection($subscriptions),
            __('Subscriptions retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions',
        summary: '➕ Subscribe a member to a plan',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Subscription created')
        ]
    )]
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
        summary: '🔍 Get subscription details',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function show($id)
    {
        $subscription = $this->subscriptionService->getSubscriptionById($id);
        return $this->successResponse(
            new PlayerSubscriptionResource($subscription),
            __('Subscription retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/freeze',
        summary: '❄️ Freeze a subscription',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Subscription frozen')
        ]
    )]
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
        summary: '🔓 Unfreeze a subscription',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Subscription unfrozen')
        ]
    )]
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
        summary: '🔄 Renew a subscription',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Subscription renewed')
        ]
    )]
    public function renew(Request $request, int $id)
    {
        $options = $request->validate([
            'coach_id' => 'nullable|integer',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $subscription = $this->subscriptionService->renewSubscription($id, $options);
        return $this->successResponse(
            new PlayerSubscriptionResource($subscription->load(['plan'])),
            __('Subscription renewed successfully'),
            201
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/cancel',
        summary: '❌ Cancel a subscription',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Subscription cancelled')
        ]
    )]
    public function cancel(Request $request, int $id)
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $subscription = $this->subscriptionService->cancelSubscription($id, $data['reason'] ?? null);
        return $this->successResponse(
            new PlayerSubscriptionResource($subscription),
            __('Subscription cancelled successfully')
        );
    }

    #[OA\Post(
        path: '/v1/player-subscriptions/{id}/payment',
        summary: '💳 Record a payment for a subscription',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Payment recorded')
        ]
    )]
    public function recordPayment(Request $request, int $id)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $subscription = $this->subscriptionService->recordPayment($id, $data['amount']);
        return $this->successResponse(
            new PlayerSubscriptionResource($subscription),
            __('Payment recorded successfully')
        );
    }
}
