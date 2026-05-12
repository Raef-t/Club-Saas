<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;
use Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionResource;
use Modules\SubscriptionManager\Services\SubscriptionService;
use Modules\SubscriptionManager\Http\Requests\SubscribeMemberRequest;
use Modules\SubscriptionManager\Http\Requests\FreezeSubscriptionRequest;
use Modules\Core\Http\Controllers\Api\BaseController;
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
        $subscriptions = $this->subscriptionRepository->all();
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
            new PlayerSubscriptionResource($subscription->load(['member', 'plan'])),
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
        $subscription = $this->subscriptionRepository->find($id);
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
            new PlayerSubscriptionResource($subscription->load(['member', 'plan'])),
            __('Subscription frozen successfully')
        );
    }
}
