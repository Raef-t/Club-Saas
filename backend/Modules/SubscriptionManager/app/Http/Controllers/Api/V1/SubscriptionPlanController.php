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
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ قائمة خطط الاشتراك',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription plans retrieved successfully'),
                new OA\Property(
                    property: 'data', 
                    type: 'array', 
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'subscription_number', type: 'string', example: '25487965'),
                            new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-08-01'),
                            new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                            new OA\Property(property: 'max_subscribers', type: 'integer', example: 50),
                            new OA\Property(property: 'current_subscribers', type: 'integer', example: 10),
                            new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', example: false),
                            new OA\Property(property: 'activities', type: 'array', items: new OA\Items(type: 'object'))
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request)
    {
        $query = \Modules\SubscriptionManager\Models\SubscriptionPlan::active()->with(['planActivities', 'sessionTemplates']);
        
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->boolean('available', true)) {
            $query->available();
        }
        
        $plans = $query->get();
        return $this->successResponse(
            SubscriptionPlanResource::collection($plans),
            __('Subscription plans retrieved successfully')
        );
    }

    #[OA\Get(
        path: '/v1/subscription-plans/registration',
        summary: '📋 عرض خطط الاشتراك المتاحة للتسجيل',
        description: 'استرجاع قائمة بخطط الاشتراك المتاحة للتسجيل فقط (التي لم تتجاوز الحد الأقصى للمشتركين) مع جلب الأنشطة المرتبطة بكل خطة.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ قائمة خطط الاشتراك المتاحة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Registration plans retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    public function registrationPlans(\Illuminate\Http\Request $request)
    {
        // Get active plans that have available capacity, and eager load their activities
        $query = \Modules\SubscriptionManager\Models\SubscriptionPlan::active()
            ->available()
            ->with(['planActivities', 'sessionTemplates']);
            
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $plans = $query->get();
            
        return $this->successResponse(
            \Modules\SubscriptionManager\Http\Resources\SubscriptionPlanRegistrationResource::collection($plans),
            __('Registration plans retrieved successfully')
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
            required: ['branch_id', 'name', 'type', 'base_price'],
            properties: [
                new OA\Property(property: 'branch_id', type: 'integer', description: '(مطلوب) معرف الفرع', example: 1),
                new OA\Property(property: 'name', type: 'string', description: 'اسم الخطة', example: 'الاشتراك الذهبي'),
                new OA\Property(property: 'type', type: 'string', enum: ['fixed_period', 'session_based'], example: 'fixed_period'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-08-01'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                new OA\Property(property: 'duration_days', type: 'integer', example: 30),
                new OA\Property(property: 'session_count', type: 'integer', nullable: true, example: null),
                new OA\Property(property: 'base_price', type: 'number', format: 'float', example: 350.00),
                new OA\Property(property: 'max_subscribers', type: 'integer', nullable: true, example: 50),
                new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', nullable: true, example: false),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(
                    property: 'activities', 
                    type: 'array', 
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'staff_activity_id', type: 'integer', nullable: true, example: 1)
                        ]
                    )
                ),
                new OA\Property(
                    property: 'session_templates',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'facility_id', type: 'integer', nullable: true, example: 1),
                            new OA\Property(property: 'day_of_week', type: 'integer', example: 0),
                            new OA\Property(property: 'start_time', type: 'string', example: '08:00'),
                            new OA\Property(property: 'end_time', type: 'string', example: '09:00'),
                            new OA\Property(property: 'gender_allowed', type: 'string', example: 'both')
                        ]
                    )
                )
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
                new OA\Property(
                    property: 'data', 
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'subscription_number', type: 'string', example: '25487965'),
                        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-08-01'),
                        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                        new OA\Property(property: 'max_subscribers', type: 'integer', example: 50),
                        new OA\Property(property: 'current_subscribers', type: 'integer', example: 10),
                        new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', example: false)
                    ]
                )
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
                new OA\Property(
                    property: 'data', 
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'subscription_number', type: 'string', example: '25487965'),
                        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-08-01'),
                        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                        new OA\Property(property: 'max_subscribers', type: 'integer', example: 50),
                        new OA\Property(property: 'current_subscribers', type: 'integer', example: 10),
                        new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', example: false),
                        new OA\Property(property: 'activities', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخطة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $plan = $this->planRepository->find($id);
        $plan->loadMissing(['planActivities', 'sessionTemplates']);
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
                new OA\Property(property: 'name', type: 'string', description: 'اسم الخطة', example: 'الاشتراك الماسي'),
                new OA\Property(property: 'type', type: 'string', enum: ['fixed_period', 'session_based'], example: 'fixed_period'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-08-01'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                new OA\Property(property: 'duration_days', type: 'integer', example: 30),
                new OA\Property(property: 'session_count', type: 'integer', nullable: true, example: null),
                new OA\Property(property: 'base_price', type: 'number', format: 'float', example: 400.00),
                new OA\Property(property: 'max_subscribers', type: 'integer', nullable: true, example: 50),
                new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', nullable: true, example: false),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(
                    property: 'activities', 
                    type: 'array', 
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'staff_activity_id', type: 'integer', nullable: true, example: 1)
                        ]
                    )
                ),
                new OA\Property(
                    property: 'session_templates',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'facility_id', type: 'integer', nullable: true, example: 1),
                            new OA\Property(property: 'day_of_week', type: 'integer', example: 0),
                            new OA\Property(property: 'start_time', type: 'string', example: '08:00'),
                            new OA\Property(property: 'end_time', type: 'string', example: '09:00'),
                            new OA\Property(property: 'gender_allowed', type: 'string', example: 'both')
                        ]
                    )
                )
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
                new OA\Property(
                    property: 'data', 
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'subscription_number', type: 'string', example: '25487965'),
                        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true, example: '2026-08-01'),
                        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                        new OA\Property(property: 'max_subscribers', type: 'integer', example: 50),
                        new OA\Property(property: 'current_subscribers', type: 'integer', example: 10),
                        new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', example: false)
                    ]
                )
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
