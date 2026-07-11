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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use OpenApi\Attributes as OA;

class OfferController extends BaseController
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
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
        $query = Offer::with(['plans' => function($q) {
            $q->where('is_active', true);
        }]);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $offers = $query->latest()->get();

        // Optional: filter out offers where at least one plan is fully booked
        if (filter_var($request->input('available_only'), FILTER_VALIDATE_BOOLEAN)) {
            $offers = $offers->filter(function ($offer) {
                foreach ($offer->plans as $plan) {
                    if ($plan->max_subscribers > 0 && $plan->current_subscribers >= $plan->max_subscribers) {
                        return false;
                    }
                }
                return true;
            });
        }

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
        $data = $request->validated();
        
        DB::beginTransaction();
        try {
            $offer = Offer::create([
                'branch_id' => $data['branch_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => Auth::id(),
            ]);

            $offer->plans()->sync($data['plans']);

            DB::commit();

            $offer->load('plans');
            return $this->successResponse(
                new OfferResource($offer),
                __('Offer created successfully'),
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
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
        $offer = Offer::with('plans')->findOrFail($id);
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
        $offer = Offer::findOrFail($id);
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $offer->update($data);

            if (isset($data['plans'])) {
                $offer->plans()->sync($data['plans']);
            }

            DB::commit();

            $offer->load('plans');
            return $this->successResponse(
                new OfferResource($offer),
                __('Offer updated successfully')
            );
        } catch (Exception $e) {
            DB::rollBack();
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
        $offer = Offer::findOrFail($id);
        $offer->delete();

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
