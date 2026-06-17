<?php
namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Services\PlayerSubscriptionItemService;
use Modules\SubscriptionManager\Http\Requests\StorePlayerSubscriptionItemRequest;
use Modules\SubscriptionManager\Http\Requests\UpdatePlayerSubscriptionItemRequest;
use Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionItemResource;
use OpenApi\Attributes as OA;

class PlayerSubscriptionItemController extends BaseController
{
    protected $service;

    public function __construct(PlayerSubscriptionItemService $service) {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/player-subscription-items',
        summary: '📋 عرض عناصر اشتراك اللاعب',
        description: 'استرجاع جميع العناصر المرتبطة باشتراكات اللاعبين.',
        tags: ['Player Subscription Items'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع العناصر بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index() {
        return $this->successResponse(PlayerSubscriptionItemResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/player-subscription-items',
        summary: '➕ إضافة عنصر لاشتراك اللاعب',
        description: 'إضافة عنصر جديد ضمن اشتراك لاعب.',
        tags: ['Player Subscription Items'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['player_subscription_id', 'item_id', 'quantity'],
            properties: [
                new OA\Property(property: 'player_subscription_id', type: 'integer', example: 1),
                new OA\Property(property: 'item_id', type: 'integer', example: 1),
                new OA\Property(property: 'quantity', type: 'integer', example: 2)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم الإضافة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StorePlayerSubscriptionItemRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new PlayerSubscriptionItemResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/player-subscription-items/{id}',
        summary: '🔍 تفاصيل عنصر الاشتراك',
        description: 'استرجاع تفاصيل عنصر محدد باشتراك لاعب.',
        tags: ['Player Subscription Items'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العنصر', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل العنصر',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id) {
        return $this->successResponse(new PlayerSubscriptionItemResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/player-subscription-items/{id}',
        summary: '📝 تعديل عنصر الاشتراك',
        description: 'تحديث بيانات عنصر ضمن اشتراك لاعب.',
        tags: ['Player Subscription Items'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العنصر', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'quantity', type: 'integer', example: 3)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التعديل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdatePlayerSubscriptionItemRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new PlayerSubscriptionItemResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/player-subscription-items/{id}',
        summary: '🗑️ حذف عنصر الاشتراك',
        description: 'إزالة عنصر من اشتراك لاعب.',
        tags: ['Player Subscription Items'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العنصر', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
