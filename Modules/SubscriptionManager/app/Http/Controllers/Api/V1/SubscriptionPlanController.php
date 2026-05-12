<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\SubscriptionManager\Repositories\SubscriptionPlanRepositoryInterface;
use Modules\SubscriptionManager\Http\Resources\SubscriptionPlanResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

use Modules\SubscriptionManager\Http\Requests\StoreSubscriptionPlanRequest;

class SubscriptionPlanController extends BaseController
{
    protected $planRepository;

    public function __construct(SubscriptionPlanRepositoryInterface $planRepository)
    {
        $this->planRepository = $planRepository;
    }

    #[OA\Get(
        path: '/v1/subscription-plans',
        summary: '📋 List all subscription plans',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        $plans = $this->planRepository->all();
        return $this->successResponse(
            SubscriptionPlanResource::collection($plans),
            __('Subscription plans retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/subscription-plans',
        summary: '➕ Create a new plan',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Plan created')
        ]
    )]
    public function store(StoreSubscriptionPlanRequest $request)
    {
        $plan = $this->planRepository->create($request->validated());
        return $this->successResponse(
            new SubscriptionPlanResource($plan),
            __('Subscription plan created successfully'),
            201
        );
    }

    #[OA\Get(
        path: '/v1/subscription-plans/{id}',
        summary: '🔍 Get plan details',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function show($id)
    {
        $plan = $this->planRepository->find($id);
        return $this->successResponse(
            new SubscriptionPlanResource($plan),
            __('Subscription plan retrieved successfully')
        );
    }

    #[OA\Put(
        path: '/v1/subscription-plans/{id}',
        summary: '📝 Update plan',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Plan updated')
        ]
    )]
    public function update(StoreSubscriptionPlanRequest $request, $id)
    {
        $plan = $this->planRepository->update($id, $request->validated());
        return $this->successResponse(
            new SubscriptionPlanResource($plan),
            __('Subscription plan updated successfully')
        );
    }

    #[OA\Delete(
        path: '/v1/subscription-plans/{id}',
        summary: '🗑️ Delete plan',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Plan deleted')
        ]
    )]
    public function destroy($id)
    {
        $this->planRepository->delete($id);
        return $this->successResponse(null, __('Subscription plan deleted successfully'));
    }
}
