<?php

namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Http\Resources\ActivityTypeResource;
use Modules\Sports\Http\Requests\ActivityTypeRequest;
use OpenApi\Attributes as OA;

class ActivityTypeController extends BaseController
{
    #[OA\Get(
        path: '/v1/activity-types',
        summary: '📋 عرض جميع أنواع الأنشطة',
        description: 'استرجاع قائمة بجميع أنواع الأنشطة الرياضية.',
        tags: ['Activity Types'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ قائمة أنواع الأنشطة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity types retrieved successfully'),
                new OA\Property(
                    property: 'data', 
                    type: 'array', 
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'object', properties: [
                                new OA\Property(property: 'ar', type: 'string', example: 'صالة مفتوحة'),
                                new OA\Property(property: 'en', type: 'string', example: 'open_gym')
                            ]),
                            new OA\Property(property: 'is_active', type: 'boolean', example: true)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request)
    {
        $query = ActivityType::query();
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        $types = $query->get();
        return $this->successResponse(
            ActivityTypeResource::collection($types),
            __('Activity types retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/activity-types',
        summary: '➕ إنشاء نوع نشاط جديد',
        description: 'إضافة نوع نشاط رياضي جديد إلى النظام.',
        tags: ['Activity Types'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'branch_id'],
            properties: [
                new OA\Property(property: 'branch_id', description: '(مطلوب) معرف الفرع', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'object', properties: [
                    new OA\Property(property: 'ar', type: 'string', example: 'صالة مفتوحة'),
                    new OA\Property(property: 'en', type: 'string', example: 'open_gym')
                ]),
                new OA\Property(property: 'is_active', type: 'boolean', example: true)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء نوع النشاط بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity type created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(ActivityTypeRequest $request)
    {
        $type = ActivityType::create($request->validated());
        return $this->successResponse(
            new ActivityTypeResource($type),
            __('Activity type created successfully'),
            201
        );
    }

    #[OA\Get(
        path: '/v1/activity-types/{activity_type}',
        summary: '🔍 عرض تفاصيل نوع النشاط',
        description: 'استرجاع بيانات نوع نشاط محدد.',
        tags: ['Activity Types'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'activity_type', in: 'path', required: true, description: 'معرف نوع النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل نوع النشاط',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity type retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على نوع النشاط', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show(ActivityType $activity_type)
    {
        return $this->successResponse(
            new ActivityTypeResource($activity_type),
            __('Activity type retrieved successfully')
        );
    }

    #[OA\Put(
        path: '/v1/activity-types/{activity_type}',
        summary: '📝 تحديث نوع نشاط',
        description: 'تعديل بيانات نوع نشاط محدد.',
        tags: ['Activity Types'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'activity_type', in: 'path', required: true, description: 'معرف نوع النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'object', properties: [
                    new OA\Property(property: 'ar', type: 'string', example: 'صالة مفتوحة'),
                    new OA\Property(property: 'en', type: 'string', example: 'open_gym')
                ]),
                new OA\Property(property: 'is_active', type: 'boolean', example: true)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التحديث بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity type updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على نوع النشاط', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(ActivityTypeRequest $request, ActivityType $activity_type)
    {
        $activity_type->update($request->validated());
        return $this->successResponse(
            new ActivityTypeResource($activity_type),
            __('Activity type updated successfully')
        );
    }

    #[OA\Delete(
        path: '/v1/activity-types/{activity_type}',
        summary: '🗑️ حذف نوع نشاط',
        description: 'إزالة نوع نشاط من النظام.',
        tags: ['Activity Types'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'activity_type', in: 'path', required: true, description: 'معرف نوع النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity type deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على نوع النشاط', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(ActivityType $activity_type)
    {
        $activity_type->delete();
        return $this->successResponse(null, __('Activity type deleted successfully'));
    }
}
