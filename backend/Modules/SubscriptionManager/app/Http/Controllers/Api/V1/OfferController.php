<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Models\Offer;
use Modules\SubscriptionManager\Http\Requests\StoreOfferRequest;
use Modules\SubscriptionManager\Http\Requests\UpdateOfferRequest;
use Modules\SubscriptionManager\Http\Requests\SubscribeOfferRequest;
use Modules\SubscriptionManager\Http\Resources\OfferResource;
use Modules\SubscriptionManager\Http\Resources\InvoiceResource;
use Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionResource;
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
        summary: '👥 عرض الباقات والعروض الترويجية',
        description: 'استرجاع قائمة بجميع العروض الترويجية المتاحة. يمكن التصفية حسب معرف الفرع أو الترقيم.',
        tags: ['Offers'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية العروض بناءً على معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ قائمة العروض الترويجية المتاحة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Offers retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'باقة الصيف الرياضية'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'اشتراك سباحة + لياقة بدنية بسعر مخفض'),
                            new OA\Property(property: 'price', type: 'number', format: 'float', example: 1500.00),
                            new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-06-01'),
                            new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2026-08-31'),
                            new OA\Property(property: 'is_active', type: 'boolean', example: true),
                            new OA\Property(
                                property: 'plans',
                                type: 'array',
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'سباحة أطفال')
                                    ]
                                )
                            ),
                            new OA\Property(property: 'created_at', type: 'string', example: '2026-06-01T10:00:00.000000Z'),
                            new OA\Property(property: 'updated_at', type: 'string', example: '2026-06-01T10:00:00.000000Z')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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
        summary: '➕ إنشاء عرض ترويجي جديد',
        description: 'إنشاء عرض جديد وتحديد السعر الإجمالي وربطه بمجموعة من خطط الأنشطة.',
        tags: ['Offers'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['branch_id', 'name', 'price', 'plans'],
            properties: [
                new OA\Property(property: 'branch_id', description: '(مطلوب) معرف الفرع', type: 'integer', example: 1),
                new OA\Property(property: 'name', description: '(مطلوب) اسم العرض', type: 'string', example: 'عرض العيد الوطني'),
                new OA\Property(property: 'description', description: '(اختياري) وصف العرض', type: 'string', nullable: true, example: 'خصومات حصرية لمشتركي النادي'),
                new OA\Property(property: 'price', description: '(مطلوب) السعر الإجمالي', type: 'number', format: 'float', example: 1200.00),
                new OA\Property(property: 'start_date', description: '(اختياري) تاريخ البداية', type: 'string', format: 'date', example: '2026-09-20'),
                new OA\Property(property: 'end_date', description: '(اختياري) تاريخ النهاية', type: 'string', format: 'date', example: '2026-09-30'),
                new OA\Property(property: 'is_active', description: 'حالة تفعيل العرض', type: 'boolean', example: true),
                new OA\Property(
                    property: 'plans',
                    description: '(مطلوب) مصفوفة معرفات خطط الاشتراكات المربوطة بالعرض',
                    type: 'array',
                    items: new OA\Items(type: 'integer', example: 1)
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء العرض بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Offer created successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 2),
                        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'عرض العيد الوطني'),
                        new OA\Property(property: 'description', type: 'string', example: 'خصومات حصرية لمشتركي النادي'),
                        new OA\Property(property: 'price', type: 'number', example: 1200.00),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', example: '2026-09-06T12:45:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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
        summary: '🔍 تفاصيل العرض الترويجي',
        description: 'استرجاع تفاصيل عرض ترويجي محدد والخطط المربوطة به.',
        tags: ['Offers'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العرض', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل العرض الترويجي',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Offer retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'باقة الصيف الرياضية'),
                        new OA\Property(property: 'description', type: 'string', example: 'اشتراك سباحة + لياقة بدنية بسعر مخفض'),
                        new OA\Property(property: 'price', type: 'number', example: 1500.00),
                        new OA\Property(property: 'start_date', type: 'string', example: '2026-06-01'),
                        new OA\Property(property: 'end_date', type: 'string', example: '2026-08-31'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'plans',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'سباحة أطفال')
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 العرض غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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
        summary: '✏️ تعديل العرض الترويجي',
        description: 'تحديث بيانات العرض والأسعار والخطط المرتبطة به.',
        tags: ['Offers'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العرض', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'عرض الشتاء المميز'),
                new OA\Property(property: 'description', type: 'string', example: 'وصف العرض بعد التعديل'),
                new OA\Property(property: 'price', type: 'number', example: 800.00),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-12-01'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2027-01-31'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'plans', type: 'array', items: new OA\Items(type: 'integer', example: 1))
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث العرض بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Offer updated successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'عرض الشتاء المميز'),
                        new OA\Property(property: 'price', type: 'number', example: 800.00),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2026-09-06T12:45:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 العرض غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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
        summary: '🗑️ حذف العرض الترويجي',
        description: 'حذف العرض من النظام ناعماً. يتطلب تأكيد الحذف بنص "delete" في حال وجود اشتراكات نشطة حالية.',
        tags: ['Offers'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'offer', in: 'path', required: true, description: 'معرف العرض', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'confirm', in: 'query', required: false, description: 'كلمة التأكيد (delete) لإتمام عملية الحذف', schema: new OA\Schema(type: 'string', example: 'delete'))]
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
    #[OA\Response(
        response: 422, 
        description: '⚠️ يتطلب تأكيد الحذف بكلمة delete لوجود اشتراكات نشطة', 
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'), 
                new OA\Property(property: 'message', type: 'string', example: 'تنبيه: يوجد 4 اشتراك(ات) نشطة حالية لهذا العرض. أرسل "delete" للتأكيد.')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 العرض غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(Request $request, int $id)
    {
        $offer = \Modules\SubscriptionManager\Models\Offer::findOrFail($id);

        $activeSubsCount = \Modules\SubscriptionManager\Models\PlayerSubscription::where('offer_id', $id)
            ->where('status', \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE->value)
            ->count();

        $confirm = strtolower(trim($request->input('confirm') ?? $request->input('confirmation') ?? $request->input('confirm_text') ?? ''));

        if ($confirm !== 'delete') {
            if ($activeSubsCount > 0) {
                return $this->errorResponse(
                    __('تنبيه: يوجد :count اشتراك(ات) نشطة حالية لهذا العرض. حذف العرض سيؤدي إلى إلغاء إمكانية حضورهم. هل أنت متأكد؟ أرسل "delete" للتأكيد.', ['count' => $activeSubsCount]),
                    422
                );
            }

            return $this->errorResponse(
                __('سيتم حذف هذا العرض وكافة بنود الاشتراكات المنتهية المرتبطة به، هل أنت متأكد؟ أرسل "delete" للتأكيد.'),
                422
            );
        }

        $offer->delete();
        return $this->successResponse(null, __('Offer deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/offers/trashed',
        summary: '🗑️ عرض العروض المحذوفة (سلة المهملات)',
        description: 'جلب قائمة بالعروض المحذوفة ناعماً مع إمكانية الترقيم.',
        tags: ['Offers'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب العروض المحذوفة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Trashed offers retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 3),
                            new OA\Property(property: 'name', type: 'string', example: 'عرض الصيف السابق'),
                            new OA\Property(property: 'deleted_at', type: 'string', example: '2026-08-01T10:00:00.000000Z')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function trashed(Request $request)
    {
        $query = \Modules\SubscriptionManager\Models\Offer::onlyTrashed();

        if ($request->has('per_page') && $request->input('per_page') !== 'all') {
            $perPage = min(max((int) $request->input('per_page'), 1), 100);
            $offers = $query->paginate($perPage);
        } else {
            $offers = $query->get();
        }

        return $this->successResponse(OfferResource::collection($offers), __('Trashed offers retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/offers/{id}/restore',
        summary: '♻️ استرجاع عرض محذوف',
        description: 'استرجاع العرض الترويجي وكافة الاشتراكات التابعة له من سلة المهملات.',
        tags: ['Offers'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العرض', schema: new OA\Schema(type: 'integer', example: 3))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع العرض بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Offer restored successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 3),
                        new OA\Property(property: 'name', type: 'string', example: 'عرض الصيف السابق'),
                        new OA\Property(property: 'deleted_at', type: 'string', nullable: true, example: null)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 العرض غير موجود في سلة المهملات', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function restore($id)
    {
        $offer = \Modules\SubscriptionManager\Models\Offer::onlyTrashed()->findOrFail($id);
        $offer->restore();
        return $this->successResponse(new OfferResource($offer), __('Offer restored successfully'));
    }

    #[OA\Post(
        path: '/v1/offers/{id}/subscribe',
        summary: '📝 اشتراك لاعب في عرض ترويجي',
        description: 'اشتراك لاعب في عرض معين وتوليد الفاتورة والاشتراكات المقترنة بجميع الخطط التابعة للعرض أوتوماتيكياً.',
        tags: ['Offers'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العرض الترويجي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id', 'paid_amount'],
            properties: [
                new OA\Property(property: 'member_id', description: '(مطلوب) معرف اللاعب', type: 'integer', example: 5),
                new OA\Property(property: 'paid_amount', description: '(مطلوب) المبلغ المدفوع', type: 'number', format: 'float', example: 1200.00),
                new OA\Property(property: 'payment_method', description: 'طريقة الدفع (cash, card, wallet, bank_transfer)', type: 'string', enum: ['cash', 'card', 'wallet', 'bank_transfer'], example: 'cash'),
                new OA\Property(property: 'receipt_number', description: '(اختياري) رقم إيصال القبض', type: 'string', nullable: true, example: 'REC-2026-105'),
                new OA\Property(property: 'start_date', description: '(اختياري) تاريخ بداية الاشتراك (YYYY-MM-DD)', type: 'string', format: 'date', example: '2026-09-15'),
                new OA\Property(property: 'notes', description: '(اختياري) ملاحظات إضافية', type: 'string', nullable: true, example: 'اشتراك العيد الوطني')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إكمال الاشتراك وتوليد الفاتورة والاشتراكات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member subscribed to offer successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'invoice',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 45),
                                new OA\Property(property: 'member_id', type: 'integer', example: 5),
                                new OA\Property(property: 'total_amount', type: 'number', example: 1200.00),
                                new OA\Property(property: 'paid_amount', type: 'number', example: 1200.00),
                                new OA\Property(property: 'status', type: 'string', example: 'paid'),
                                new OA\Property(property: 'payment_method', type: 'string', example: 'cash')
                            ]
                        ),
                        new OA\Property(
                            property: 'subscriptions',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 101),
                                    new OA\Property(property: 'member_id', type: 'integer', example: 5),
                                    new OA\Property(property: 'plan_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'status', type: 'string', example: 'active'),
                                    new OA\Property(property: 'start_date', type: 'string', example: '2026-09-15'),
                                    new OA\Property(property: 'end_date', type: 'string', example: '2026-10-15')
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في تنفيذ العملية أو بيانات صحة الاشتراك', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'المبلغ المدفوع غير كافٍ.')]))]
    #[OA\Response(response: 404, description: '🚫 العرض أو اللاعب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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
                [
                    'invoice' => new InvoiceResource($invoicesAndSubscriptions['invoice']),
                    'subscriptions' => PlayerSubscriptionResource::collection($invoicesAndSubscriptions['subscriptions']),
                ],
                __('Member subscribed to offer successfully'),
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
