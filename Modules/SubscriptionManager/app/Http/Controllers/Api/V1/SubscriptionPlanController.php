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
        summary: '📋 عرض جميع خطط الاشتراك',
        description: 'استرجاع قائمة بجميع خطط الاشتراك المتوفرة في النادي.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ قائمة خطط الاشتراك',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription plans retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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
        summary: '➕ إنشاء خطة اشتراك جديدة',
        description: 'إنشاء خطة اشتراك جديدة يمكن للأعضاء الاشتراك بها.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'duration_in_days', 'price'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'الاشتراك الذهبي'),
                new OA\Property(property: 'duration_in_days', type: 'integer', example: 30),
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 350.00)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الخطة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription plan created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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
        path: '/v1/subscription-plans/{subscription_plan}',
        summary: '🔍 تفاصيل خطة الاشتراك',
        description: 'استرجاع تفاصيل خطة اشتراك محددة.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan', in: 'path', required: true, description: 'معرف الخطة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل خطة الاشتراك',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription plan retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخطة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $plan = $this->planRepository->find($id);
        return $this->successResponse(
            new SubscriptionPlanResource($plan),
            __('Subscription plan retrieved successfully')
        );
    }

    #[OA\Put(
        path: '/v1/subscription-plans/{subscription_plan}',
        summary: '📝 تعديل خطة الاشتراك',
        description: 'تحديث بيانات ومميزات خطة اشتراك.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan', in: 'path', required: true, description: 'معرف الخطة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'الاشتراك الماسي')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الخطة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription plan updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخطة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(StoreSubscriptionPlanRequest $request, $id)
    {
        $plan = $this->planRepository->update($id, $request->validated());
        return $this->successResponse(
            new SubscriptionPlanResource($plan),
            __('Subscription plan updated successfully')
        );
    }

    #[OA\Delete(
        path: '/v1/subscription-plans/{subscription_plan}',
        summary: '🗑️ حذف خطة الاشتراك',
        description: 'إزالة خطة اشتراك من النظام.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan', in: 'path', required: true, description: 'معرف الخطة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الخطة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription plan deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخطة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id)
    {
        $this->planRepository->delete($id);
        return $this->successResponse(null, __('Subscription plan deleted successfully'));
    }
}
