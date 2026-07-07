<?php
namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Services\SubscriptionPlanActivityService;
use Modules\SubscriptionManager\Http\Requests\StoreSubscriptionPlanActivityRequest;
use Modules\SubscriptionManager\Http\Requests\UpdateSubscriptionPlanActivityRequest;
use Modules\SubscriptionManager\Http\Resources\SubscriptionPlanActivityResource;
use OpenApi\Attributes as OA;

class SubscriptionPlanActivityController extends BaseController
{
    protected $service;

    public function __construct(SubscriptionPlanActivityService $service) {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/subscription-plan-activities',
        summary: '🏋️ عرض نشاطات الخطط',
        description: 'استرجاع الأنشطة المرتبطة بخطط الاشتراك. يمكن التصفية حسب الفرع.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية الأنشطة حسب الفرع المرتبط بالخطة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع النشاطات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request) {
        $filters = $request->only(['branch_id']);
        return $this->successResponse(SubscriptionPlanActivityResource::collection($this->service->getAll($filters)), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/subscription-plan-activities',
        summary: '➕ إضافة نشاط لخطة',
        description: 'ربط نشاط (كالسباحة، الحديد) بخطة اشتراك.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['plan_id', 'activity_id'],
            properties: [
                new OA\Property(property: 'plan_id', type: 'integer', example: 1),
                new OA\Property(property: 'activity_id', type: 'integer', example: 1),
                new OA\Property(property: 'coach_id', type: 'integer', description: '(مطلوب) المدرب المشرف', example: 5),
                new OA\Property(property: 'sessions_count', type: 'integer', example: 12),
                new OA\Property(property: 'is_unlimited', type: 'boolean', example: false)
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
    public function store(StoreSubscriptionPlanActivityRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new SubscriptionPlanActivityResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/subscription-plan-activities/{subscription_plan_activity}',
        summary: '🔍 تفاصيل نشاط الخطة',
        description: 'استرجاع تفاصيل ارتباط نشاط بخطة.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan_activity', in: 'path', required: true, description: 'المعرف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل النشاط',
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
        return $this->successResponse(new SubscriptionPlanActivityResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/subscription-plan-activities/{subscription_plan_activity}',
        summary: '📝 تعديل نشاط الخطة',
        description: 'تحديث بيانات ارتباط النشاط بالخطة.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan_activity', in: 'path', required: true, description: 'المعرف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'activity_id', type: 'integer', example: 2),
                new OA\Property(property: 'coach_id', type: 'integer', description: '(مطلوب) المدرب المشرف', example: 5),
                new OA\Property(property: 'sessions_count', type: 'integer', example: 12),
                new OA\Property(property: 'is_unlimited', type: 'boolean', example: false)
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
    public function update(UpdateSubscriptionPlanActivityRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new SubscriptionPlanActivityResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/subscription-plan-activities/{subscription_plan_activity}',
        summary: '🗑️ حذف نشاط من الخطة',
        description: 'إزالة ارتباط نشاط بخطة اشتراك.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan_activity', in: 'path', required: true, description: 'المعرف', schema: new OA\Schema(type: 'integer', example: 1))]
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
