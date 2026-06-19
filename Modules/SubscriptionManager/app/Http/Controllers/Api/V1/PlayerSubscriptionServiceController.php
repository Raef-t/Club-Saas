<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Modules\SubscriptionManager\Models\PlayerSubscriptionService;
use Modules\SubscriptionManager\Http\Requests\StorePlayerSubscriptionServiceRequest;
use Modules\SubscriptionManager\Http\Requests\UpdatePlayerSubscriptionServiceRequest;
use Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionServiceResource;
use OpenApi\Attributes as OA;

class PlayerSubscriptionServiceController extends Controller
{
    #[OA\Get(
        path: '/v1/player-subscription-services',
        summary: '📋 عرض خدمات اشتراك اللاعب',
        description: 'استرجاع جميع الخدمات الإضافية المرتبطة باشتراكات اللاعبين.',
        tags: ['Player Subscription Services'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الخدمات بنجاح',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(type: 'object')
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index()
    {
        $services = PlayerSubscriptionService::all();
        return PlayerSubscriptionServiceResource::collection($services);
    }

    #[OA\Post(
        path: '/v1/player-subscription-services',
        summary: '➕ إضافة خدمة لاشتراك اللاعب',
        description: 'إضافة خدمة إضافية جديدة لاشتراك لاعب.',
        tags: ['Player Subscription Services'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['player_subscription_id', 'service_id'],
            properties: [
                new OA\Property(property: 'player_subscription_id', type: 'integer', example: 1),
                new OA\Property(property: 'service_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم الإضافة بنجاح',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StorePlayerSubscriptionServiceRequest $request)
    {
        $service = PlayerSubscriptionService::create($request->validated());
        return new PlayerSubscriptionServiceResource($service);
    }

    #[OA\Get(
        path: '/v1/player-subscription-services/{player_subscription_service}',
        summary: '🔍 تفاصيل خدمة الاشتراك',
        description: 'استرجاع تفاصيل خدمة محددة باشتراك لاعب.',
        tags: ['Player Subscription Services'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'player_subscription_service', in: 'path', required: true, description: 'معرف الخدمة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل الخدمة',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $service = PlayerSubscriptionService::findOrFail($id);
        return new PlayerSubscriptionServiceResource($service);
    }

    #[OA\Put(
        path: '/v1/player-subscription-services/{player_subscription_service}',
        summary: '📝 تعديل خدمة الاشتراك',
        description: 'تحديث بيانات خدمة إضافية ضمن اشتراك لاعب.',
        tags: ['Player Subscription Services'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'player_subscription_service', in: 'path', required: true, description: 'معرف الخدمة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'service_id', type: 'integer', example: 2)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التعديل بنجاح',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdatePlayerSubscriptionServiceRequest $request, $id)
    {
        $service = PlayerSubscriptionService::findOrFail($id);
        $service->update($request->validated());
        return new PlayerSubscriptionServiceResource($service);
    }

    #[OA\Delete(
        path: '/v1/player-subscription-services/{player_subscription_service}',
        summary: '🗑️ حذف خدمة الاشتراك',
        description: 'إزالة خدمة من اشتراك لاعب.',
        tags: ['Player Subscription Services'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'player_subscription_service', in: 'path', required: true, description: 'معرف الخدمة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 204,
        description: '✅ تم الحذف بنجاح'
    )]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id)
    {
        $service = PlayerSubscriptionService::findOrFail($id);
        $service->delete();
        return response()->json(null, 204);
    }
}

