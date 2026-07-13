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
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية العروض بناءً على معرف الفرع',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Offers retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'عرض الصيف'),
                            new OA\Property(property: 'description', type: 'string', example: 'خصم خاص على الاشتراك'),
                            new OA\Property(property: 'price', type: 'number', example: 500.5),
                            new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-07-01'),
                            new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-08-31'),
                            new OA\Property(property: 'is_active', type: 'boolean', example: true),
                            new OA\Property(
                                property: 'plans',
                                type: 'array',
                                items: new OA\Items(type: 'object')
                            ),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                        ]
                    )
                )
            ]
        )
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
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['branch_id', 'name', 'price', 'plans'],
            properties: [
                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'عرض رمضان'),
                new OA\Property(property: 'description', type: 'string', example: 'عرض خاص بشهر رمضان'),
                new OA\Property(property: 'price', type: 'number', example: 1200),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-02-01'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-03-01'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'plans', type: 'array', items: new OA\Items(type: 'integer', example: 1))
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Offer created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Offer created successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 2),
                        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'عرض رمضان'),
                        new OA\Property(property: 'price', type: 'number', example: 1200)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: 'Validation Error')]
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
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'معرف العرض',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Offer retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'عرض الصيف'),
                        new OA\Property(property: 'description', type: 'string', example: 'خصم خاص على الاشتراك'),
                        new OA\Property(property: 'price', type: 'number', example: 500.5),
                        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-07-01'),
                        new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-08-31'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'plans',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        ),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Offer not found')]
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
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'معرف العرض',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'عرض الشتاء'),
                new OA\Property(property: 'description', type: 'string', example: 'وصف العرض بعد التعديل'),
                new OA\Property(property: 'price', type: 'number', example: 800),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-12-01'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2027-01-31'),
                new OA\Property(property: 'is_active', type: 'boolean', example: false),
                new OA\Property(property: 'plans', type: 'array', items: new OA\Items(type: 'integer', example: 1))
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Offer updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Offer updated successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'عرض الشتاء'),
                    new OA\Property(property: 'price', type: 'number', example: 800)
                ])
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Offer not found')]
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
        path: '/v1/offers/{offer}',
        summary: '🗑️ حذف العرض',
        description: 'حذف العرض من النظام. لا يمكن حذفه إذا كان هناك أعضاء اشتركوا من خلاله.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'offer', in: 'path', required: true, description: 'معرف العرض', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف العرض بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Offer deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 409, description: '🚫 لا يمكن الحذف — العرض مرتبط باشتراكات', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'لا يمكن حذف العرض.')]))]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على العرض', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(int $id)
    {
        $offer = \Modules\SubscriptionManager\Models\Offer::findOrFail($id);

        $subscriptionsCount = \Modules\SubscriptionManager\Models\PlayerSubscription::where('offer_id', $id)->count();

        if ($subscriptionsCount > 0) {
            return $this->errorResponse(
                "لا يمكن حذف هذا العرض لوجود {$subscriptionsCount} " . ($subscriptionsCount === 1 ? 'اشتراك مرتبط' : 'اشتراكات مرتبطة') . " به. يمكنك تعطيل العرض بدلاً من حذفه.",
                409
            );
        }

        $offer->delete();
        return $this->successResponse(null, __('Offer deleted successfully'));
    }

    #[OA\Post(
        path: '/v1/offers/{id}/subscribe',
        summary: '📝 اشتراك في عرض',
        description: 'اشتراك لاعب في عرض معين (سيتم تسجيله في جميع الخطط التابعة للعرض).',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'معرف العرض',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id', 'paid_amount'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 1, description: 'معرف اللاعب'),
                new OA\Property(property: 'paid_amount', type: 'number', example: 500, description: 'المبلغ المدفوع'),
                new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'card', 'wallet', 'bank_transfer'], example: 'cash', description: 'طريقة الدفع'),
                new OA\Property(property: 'notes', type: 'string', example: 'اشتراك جديد', description: 'ملاحظات'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-07-15', description: 'تاريخ بداية الاشتراك')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Subscribed to offer successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Member subscribed to offer successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    description: 'Array of created subscriptions and invoices',
                    items: new OA\Items(type: 'object')
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Bad Request / Validation Error')]
    #[OA\Response(response: 404, description: 'Offer or Member not found')]
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
