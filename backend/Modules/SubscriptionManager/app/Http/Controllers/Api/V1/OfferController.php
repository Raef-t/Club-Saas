<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Models\Offer;
use Modules\SubscriptionManager\Http\Requests\StoreOfferRequest;
use Modules\SubscriptionManager\Http\Requests\UpdateOfferRequest;
use Modules\SubscriptionManager\Http\Requests\SubscribeOfferRequest;
use Modules\SubscriptionManager\Http\Resources\OfferResource;
use Modules\SubscriptionManager\Services\SubscriptionService;
use Modules\SubscriptionManager\Services\OfferService;
use Exception;
use OpenApi\Attributes as OA;

class OfferController extends BaseController
{
    protected SubscriptionService $subscriptionService;
    protected OfferService $offerService;

    public function __construct(SubscriptionService $subscriptionService, OfferService $offerService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->offerService = $offerService;
    }

    #[OA\Get(
        path: '/v1/offers',
        summary: '👥 عرض الباقات/العروض',
        description: 'استرجاع قائمة بجميع العروض المتاحة. يمكن التصفية حسب الفرع.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    public function index(Request $request)
    {
        $offers = $this->offerService->getAllOffers($request->all());

        return $this->successResponse(
            OfferResource::collection($offers),
            __('Offers retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/offers',
        summary: '➕ إضافة عرض جديد',
        description: 'إنشاء عرض جديد وربطه بعدة خطط.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    public function store(StoreOfferRequest $request)
    {
        try {
            $offer = $this->offerService->createOffer($request->validated());

            return $this->successResponse(
                new OfferResource($offer),
                __('Offer created successfully'),
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: '/v1/offers/{id}',
        summary: '🔍 تفاصيل العرض',
        description: 'استرجاع تفاصيل العرض المحددة.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    public function show(int $id)
    {
        $offer = $this->offerService->getOfferById($id);
        
        return $this->successResponse(
            new OfferResource($offer),
            __('Offer retrieved successfully')
        );
    }

    #[OA\Put(
        path: '/v1/offers/{id}',
        summary: '✏️ تحديث العرض',
        description: 'تحديث بيانات العرض والخطط المرتبطة به.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    public function update(UpdateOfferRequest $request, int $id)
    {
        try {
            $offer = $this->offerService->updateOffer($id, $request->validated());

            return $this->successResponse(
                new OfferResource($offer),
                __('Offer updated successfully')
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    #[OA\Delete(
        path: '/v1/offers/{id}',
        summary: '🗑️ حذف العرض',
        description: 'حذف العرض (Soft Delete).',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    public function destroy(int $id)
    {
        $this->offerService->deleteOffer($id);

        return $this->successResponse(
            null,
            __('Offer deleted successfully')
        );
    }

    #[OA\Post(
        path: '/v1/offers/{id}/subscribe',
        summary: '📝 اشتراك في عرض',
        description: 'اشتراك لاعب في عرض معين (سيتم تسجيله في جميع الخطط).',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    public function subscribe(SubscribeOfferRequest $request, int $id)
    {
        $data = $request->validated();
        
        try {
            $invoicesAndSubscriptions = $this->subscriptionService->subscribeMemberToOffer(
                $data['member_id'],
                $id,
                $data
            );

            return $this->successResponse(
                $invoicesAndSubscriptions, // Can format with resource if needed
                __('Member subscribed to offer successfully'),
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
