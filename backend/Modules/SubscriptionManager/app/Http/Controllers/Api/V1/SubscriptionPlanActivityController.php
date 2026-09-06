<?php
namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
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
        summary: '🏋️ عرض أنشطة خطط الاشتراكات',
        description: 'استرجاع جميع الأنشطة والمدربين المربوطين بخطط الاشتراكات مع إكانية التصفية حسب الفرع والترقيم.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية الأنشطة حسب الفرع المرتبط بالخطة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع النشاطات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'plan_id', type: 'integer', example: 2),
                            new OA\Property(property: 'staff_activity_id', type: 'integer', example: 4),
                            new OA\Property(property: 'plan_name', type: 'string', example: 'خطة السباحة الشاملة'),
                            new OA\Property(
                                property: 'activity',
                                type: 'object',
                                nullable: true,
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 5),
                                    new OA\Property(property: 'name', type: 'string', example: 'سباحة أطفال')
                                ]
                            ),
                            new OA\Property(
                                property: 'coach',
                                type: 'object',
                                nullable: true,
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 3),
                                    new OA\Property(property: 'name', type: 'string', example: 'كابتن أحمد علي')
                                ]
                            ),
                            new OA\Property(property: 'created_at', type: 'string', example: '2026-01-10T10:00:00.000000Z'),
                            new OA\Property(property: 'updated_at', type: 'string', example: '2026-01-10T10:00:00.000000Z')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request) {
        return $this->successResponse(SubscriptionPlanActivityResource::collection($this->service->getAll($request->all())), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/subscription-plan-activities',
        summary: '➕ ربط نشاط بخطة اشتراك',
        description: 'إضافة ربط جديد بين نشاط رياضي ومدرب وبين خطة اشتراك محددة.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['plan_id', 'activity_id'],
            properties: [
                new OA\Property(property: 'plan_id', description: '(مطلوب) معرف خطة الاشتراك', type: 'integer', example: 2),
                new OA\Property(property: 'activity_id', description: '(مطلوب) معرف النشاط الرياضي', type: 'integer', example: 5),
                new OA\Property(property: 'coach_id', description: '(اختياري) معرف الكوتش المسند', type: 'integer', nullable: true, example: 3)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم ربط النشاط بالخطة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 10),
                        new OA\Property(property: 'plan_id', type: 'integer', example: 2),
                        new OA\Property(property: 'staff_activity_id', type: 'integer', example: 4),
                        new OA\Property(property: 'created_at', type: 'string', example: '2026-09-06T12:50:00.000000Z')
                    ]
                )
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
        description: 'استرجاع تفاصيل ارتباط نشاط محدد بخطة اشتراك.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan_activity', in: 'path', required: true, description: 'معرف ارتباط النشاط بالخطة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل النشاط المرتبط بالخطة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'plan_id', type: 'integer', example: 2),
                        new OA\Property(property: 'staff_activity_id', type: 'integer', example: 4),
                        new OA\Property(
                            property: 'activity',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 5),
                                new OA\Property(property: 'name', type: 'string', example: 'سباحة أطفال')
                            ]
                        ),
                        new OA\Property(
                            property: 'coach',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 3),
                                new OA\Property(property: 'name', type: 'string', example: 'كابتن أحمد علي')
                            ]
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 السجل غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id) {
        return $this->successResponse(new SubscriptionPlanActivityResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/subscription-plan-activities/{subscription_plan_activity}',
        summary: '📝 تعديل نشاط الخطة',
        description: 'تحديث بيانات ارتباط النشاط بالخطة وتحديث الكوتش المسند.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan_activity', in: 'path', required: true, description: 'معرف السجل', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'activity_id', description: '(اختياري) معرف النشاط الجديد', type: 'integer', example: 2),
                new OA\Property(property: 'coach_id', description: '(اختياري) معرف المدرب الجديد', type: 'integer', nullable: true, example: 3)
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'plan_id', type: 'integer', example: 2),
                        new OA\Property(property: 'staff_activity_id', type: 'integer', example: 5),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2026-09-06T12:50:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 السجل غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateSubscriptionPlanActivityRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new SubscriptionPlanActivityResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/subscription-plan-activities/{subscription_plan_activity}',
        summary: '🗑️ حذف نشاط من الخطة',
        description: 'إزالة ارتباط نشاط رياضي بخطة اشتراك بحذف ناعم.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'subscription_plan_activity', in: 'path', required: true, description: 'معرف السجل المراد حذفه', schema: new OA\Schema(type: 'integer', example: 1))]
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
    #[OA\Response(response: 404, description: '🚫 السجل غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }

    #[OA\Get(
        path: '/v1/subscription-plan-activities/trashed',
        summary: '🗑️ عرض نشاطات الخطط المحذوفة (سلة المهملات)',
        description: 'جلب قائمة بنشاطات الخطط المحذوفة ناعماً.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية الأنشطة حسب الفرع المرتبط بالخطة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب النشاطات المحذوفة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Trashed retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 8),
                            new OA\Property(property: 'plan_id', type: 'integer', example: 2),
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
        $activities = $this->service->getTrashed($request->all());
        return $this->successResponse(SubscriptionPlanActivityResource::collection($activities), 'Trashed retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/subscription-plan-activities/{id}/restore',
        summary: '♻️ استرجاع نشاط خطة محذوف',
        description: 'استرجاع نشاط خطة محذوف من سلة المهملات وإعادة ربطه بالخطة.',
        tags: ['Subscription Plan Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف ارتباط النشاط بالخطة', schema: new OA\Schema(type: 'integer', example: 8))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع النشاط بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Restored successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 8),
                        new OA\Property(property: 'plan_id', type: 'integer', example: 2),
                        new OA\Property(property: 'deleted_at', type: 'string', nullable: true, example: null)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 السجل غير موجود في سلة المهملات', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function restore($id)
    {
        $activity = $this->service->restore($id);
        return $this->successResponse(new SubscriptionPlanActivityResource($activity), 'Restored successfully');
    }
}
