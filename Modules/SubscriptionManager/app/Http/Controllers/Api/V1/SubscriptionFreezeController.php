<?php
namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Services\SubscriptionFreezeService;
use Modules\SubscriptionManager\Http\Requests\StoreSubscriptionFreezeRequest;
use Modules\SubscriptionManager\Http\Requests\UpdateSubscriptionFreezeRequest;
use Modules\SubscriptionManager\Http\Resources\SubscriptionFreezeResource;
use OpenApi\Attributes as OA;

class SubscriptionFreezeController extends BaseController
{
    protected $service;

    public function __construct(SubscriptionFreezeService $service) {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/subscription-freezes',
        summary: '❄️ عرض تجميدات الاشتراكات',
        description: 'استرجاع جميع سجلات التجميد الخاصة باشتراكات الأعضاء.',
        tags: ['Subscription Freezes'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع التجميدات بنجاح',
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
        return $this->successResponse(SubscriptionFreezeResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/subscription-freezes',
        summary: '➕ إضافة تجميد اشتراك',
        description: 'تسجيل تجميد جديد لاشتراك عضو.',
        tags: ['Subscription Freezes'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['subscription_id', 'start_date', 'end_date'],
            properties: [
                new OA\Property(property: 'subscription_id', type: 'integer', example: 1),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2023-11-01'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2023-11-15')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء التجميد بنجاح',
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
    public function store(StoreSubscriptionFreezeRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new SubscriptionFreezeResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/subscription-freezes/{subscription_freeze}',
        summary: '🔍 تفاصيل التجميد',
        description: 'استرجاع تفاصيل سجل تجميد محدد.',
        tags: ['Subscription Freezes'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_freeze', in: 'path', required: true, description: 'معرف التجميد', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل التجميد',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على التجميد', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id) {
        return $this->successResponse(new SubscriptionFreezeResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/subscription-freezes/{subscription_freeze}',
        summary: '📝 تعديل تجميد',
        description: 'تحديث بيانات تجميد لاشتراك.',
        tags: ['Subscription Freezes'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_freeze', in: 'path', required: true, description: 'معرف التجميد', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2023-11-20')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تعديل التجميد بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على التجميد', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateSubscriptionFreezeRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new SubscriptionFreezeResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/subscription-freezes/{subscription_freeze}',
        summary: '🗑️ حذف تجميد',
        description: 'حذف سجل تجميد لاشتراك.',
        tags: ['Subscription Freezes'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_freeze', in: 'path', required: true, description: 'معرف التجميد', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف التجميد بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على التجميد', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
