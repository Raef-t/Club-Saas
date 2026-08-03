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
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'تصفية حسب حالة الخطة (active, inactive, completed)', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'completed']))]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'gender', in: 'query', required: false, description: 'تصفية حسب الجنس المسموح', schema: new OA\Schema(type: 'string', enum: ['male', 'female', 'mixed']))]
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
                            new OA\Property(property: 'max_subscribers', type: 'integer', example: 50),
                            new OA\Property(property: 'current_subscribers', type: 'integer', example: 10),
                            new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', example: false),
                            new OA\Property(property: 'gender_restriction', type: 'string', example: 'mixed'),
                            new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'completed'], description: 'حالة الخطة: active (نشطة), inactive (غير نشطة), completed (مكتملة)', example: 'active'),
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
        $query = \Modules\SubscriptionManager\Models\SubscriptionPlan::with(['planActivities', 'sessionTemplates']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('gender')) {
            $query->whereIn('gender_restriction', [$request->gender, 'mixed']);
        }

        if ($request->has('available')) {
            if ($request->boolean('available')) {
                $query->available();
            }
        }
        
        $plans = $query->orderBy('id', 'desc')->get();
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
    #[OA\Parameter(name: 'gender', in: 'query', required: false, description: 'تصفية حسب الجنس المسموح', schema: new OA\Schema(type: 'string', enum: ['male', 'female', 'mixed']))]
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

        if ($request->has('gender')) {
            $query->whereIn('gender_restriction', [$request->gender, 'mixed']);
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
            required: ['branch_id', 'name', 'base_price'],
            properties: [
                new OA\Property(property: 'branch_id', type: 'integer', description: '(مطلوب) معرف الفرع', example: 1),
                new OA\Property(property: 'name', type: 'string', description: 'اسم الخطة', example: 'الاشتراك الذهبي'),
                new OA\Property(property: 'session_count', type: 'integer', nullable: true, example: null),
                new OA\Property(property: 'sessions_per_week', type: 'integer', nullable: true, example: 3),
                new OA\Property(property: 'base_price', type: 'number', format: 'float', example: 350.00),
                new OA\Property(property: 'max_subscribers', type: 'integer', nullable: true, example: 50),
                new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', nullable: true, example: false),
                new OA\Property(property: 'gender_restriction', type: 'string', enum: ['male', 'female', 'mixed'], description: '(اختياري) الجنس المسموح: male, female, mixed', example: 'mixed'),
                new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'completed'], description: '(اختياري) حالة الخطة: active, inactive, completed', example: 'active'),
                new OA\Property(
                    property: 'activities', 
                    type: 'array', 
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'activity_id', type: 'integer', nullable: false, example: 1),
                            new OA\Property(property: 'coach_id', type: 'integer', nullable: true, example: 2)
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
                            new OA\Property(property: 'end_time', type: 'string', example: '09:00')
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
                        new OA\Property(property: 'max_subscribers', type: 'integer', example: 50),
                        new OA\Property(property: 'current_subscribers', type: 'integer', example: 10),
                        new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', example: false),
                        new OA\Property(property: 'gender_restriction', type: 'string', example: 'mixed'),
                        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'completed'], description: 'حالة الخطة: active (نشطة), inactive (غير نشطة), completed (مكتملة)', example: 'active')
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
                        new OA\Property(property: 'max_subscribers', type: 'integer', example: 50),
                        new OA\Property(property: 'current_subscribers', type: 'integer', example: 10),
                        new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', example: false),
                        new OA\Property(property: 'gender_restriction', type: 'string', example: 'mixed'),
                        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'completed'], description: 'حالة الخطة: active (نشطة), inactive (غير نشطة), completed (مكتملة)', example: 'active'),
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
                new OA\Property(property: 'session_count', type: 'integer', nullable: true, example: null),
                new OA\Property(property: 'sessions_per_week', type: 'integer', nullable: true, example: 3),
                new OA\Property(property: 'base_price', type: 'number', format: 'float', example: 400.00),
                new OA\Property(property: 'max_subscribers', type: 'integer', nullable: true, example: 50),
                new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', nullable: true, example: false),
                new OA\Property(property: 'gender_restriction', type: 'string', enum: ['male', 'female', 'mixed'], description: '(اختياري) الجنس المسموح: male, female, mixed', example: 'mixed'),
                new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'completed'], description: '(اختياري) حالة الخطة: active, inactive, completed', example: 'active'),
                new OA\Property(
                    property: 'activities', 
                    type: 'array', 
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'activity_id', type: 'integer', nullable: false, example: 1),
                            new OA\Property(property: 'coach_id', type: 'integer', nullable: true, example: 2)
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
                            new OA\Property(property: 'end_time', type: 'string', example: '09:00')
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
                        new OA\Property(property: 'max_subscribers', type: 'integer', example: 50),
                        new OA\Property(property: 'current_subscribers', type: 'integer', example: 10),
                        new OA\Property(property: 'is_unlimited_subscribers', type: 'boolean', example: false),
                        new OA\Property(property: 'gender_restriction', type: 'string', example: 'mixed'),
                        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'completed'], description: 'حالة الخطة: active (نشطة), inactive (غير نشطة), completed (مكتملة)', example: 'active')
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

    #[OA\Get(
        path: '/v1/subscription-plans/{subscription_plan}/delete-check',
        summary: '🔍 فحص معلومات ومخاطر حذف خطة الاشتراك',
        description: 'استرجاع التقرير والاشتراكات والمدربين المرتبطين بالخطة لتنبيه وتخيير المستخدم قبل تنفيذ الحذف.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan', in: 'path', required: true, description: 'معرف الخطة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تقرير الفحص المسبق قبل الحذف',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Delete check report retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'plan_id', type: 'integer', example: 1),
                        new OA\Property(property: 'plan_name', type: 'string', example: 'الاشتراك الذهبي'),
                        new OA\Property(property: 'has_active_subscriptions', type: 'boolean', example: true),
                        new OA\Property(property: 'active_subscriptions_count', type: 'integer', example: 3),
                        new OA\Property(property: 'inactive_subscriptions_count', type: 'integer', example: 5),
                        new OA\Property(
                            property: 'associated_coaches',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'staff_id', type: 'integer', example: 2),
                                    new OA\Property(property: 'name', type: 'string', example: 'كابتن أحمد'),
                                    new OA\Property(property: 'role', type: 'string', example: 'coach')
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    public function deleteCheck($id)
    {
        $info = $this->planRepository->getDeleteCheckInfo($id);
        return $this->successResponse($info, __('Delete check report retrieved successfully'));
    }

    #[OA\Delete(
        path: '/v1/subscription-plans/{subscription_plan}',
        summary: '🗑️ حذف خطة الاشتراك (Soft Delete)',
        description: 'حذف خطة الاشتراك ناعماً من النظام مع خيارات حسم الاشتراكات النشطة وفك إرتباط وحذف المدربين المحددين.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan', in: 'path', required: true, description: 'معرف الخطة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'force_delete_active_subscriptions', type: 'boolean', description: 'حذف الاشتراكات النشطة للاعبين ناعماً أيضاً', example: false),
                new OA\Property(
                    property: 'detach_and_delete_staff_ids',
                    type: 'array',
                    description: 'مصفوفة معرفات المدربين المراد فك ارتباطهم بالفعالية وحذفهم ناعماً',
                    items: new OA\Items(type: 'integer', example: 2)
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الخطة وسجلاتها التابعة ناعماً بنجاح',
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
    public function destroy(\Illuminate\Http\Request $request, $id)
    {
        $options = [
            'force_delete_active_subscriptions' => $request->boolean('force_delete_active_subscriptions', false),
            'detach_and_delete_staff_ids' => $request->input('detach_and_delete_staff_ids', []),
        ];

        $this->planRepository->delete($id, $options);
        return $this->successResponse(null, __('Subscription plan deleted successfully'));
    }

    #[OA\Post(
        path: '/v1/subscription-plans/{id}/restore',
        summary: '♻️ استرجاع خطة اشتراك محذوفة',
        description: 'استرجاع خطة الاشتراك المحذوفة ناعماً وكافّة أنشطتها وقوالب جلساتها تلقائياً.',
        tags: ['Subscription Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الخطة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الخطة بنجاح')]
    #[OA\Response(response: 404, description: '🚫 الخطة غير موجودة في سلة المحذوفات')]
    public function restore($id)
    {
        $plan = \Modules\SubscriptionManager\Models\SubscriptionPlan::onlyTrashed()->findOrFail($id);
        $plan->restore();
        return $this->successResponse(null, __('Subscription plan restored successfully'));
    }
}
