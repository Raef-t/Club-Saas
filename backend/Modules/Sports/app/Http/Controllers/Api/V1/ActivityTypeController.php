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
        description: 'استرجاع قائمة بجميع أنواع الأنشطة (تُستخدم لتحديد النشاط عند إضافة وردية أو نشاط جديد).',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
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
                    items: new OA\Items(ref: '#/components/schemas/ActivityTypeResource')
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request)
    {
        $types = ActivityType::all();
        return $this->successResponse(
            ActivityTypeResource::collection($types),
            __('Activity types retrieved successfully')
        );
    }

    public function store(ActivityTypeRequest $request)
    {
        $type = ActivityType::create($request->validated());
        return $this->successResponse(
            new ActivityTypeResource($type),
            __('Activity type created successfully'),
            201
        );
    }

    public function show(ActivityType $activity_type)
    {
        return $this->successResponse(
            new ActivityTypeResource($activity_type),
            __('Activity type retrieved successfully')
        );
    }

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
        description: 'حذف نوع نشاط من النظام. لا يمكن حذفه إذا كان هناك أنشطة تابعة له.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'activity_type', in: 'path', required: true, description: 'معرف نوع النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف نوع النشاط بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity type deleted successfully')
            ]
        )
    )]
    #[OA\Response(
        response: 409, 
        description: '🚫 لا يمكن الحذف — نوع النشاط مرتبط بأنشطة أخرى', 
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'), 
                new OA\Property(property: 'message', type: 'string', example: 'لا يمكن حذف نوع النشاط لوجود 5 أنشطة تندرج تحته. يمكنك تعطيله بدلاً من حذفه.')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 نوع النشاط غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(ActivityType $activity_type)
    {
        $activitiesCount = \Modules\Sports\Models\Activity::where('activity_type_id', $activity_type->id)->count();
        if ($activitiesCount > 0) {
            return $this->errorResponse(
                "لا يمكن حذف نوع النشاط لوجود {$activitiesCount} " . ($activitiesCount === 1 ? 'نشاط يندرج' : 'أنشطة تندرج') . " تحته. يمكنك تعطيله بدلاً من حذفه.",
                409
            );
        }

        $activity_type->delete();
        return $this->successResponse(null, __('Activity type deleted successfully'));
    }
    #[OA\Patch(
        path: '/v1/activity-types/{activity_type}/settings',
        summary: '⚙️ تحديث إعدادات نوع النشاط',
        description: 'تحديث الحقول الخاصة بإعدادات نوع النشاط (يعتمد على جلسات، عدد المشتركين لا نهائي، أو نظام الورديات) فقط.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'activity_type', description: 'ID of the activity type', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'is_session_based', type: 'boolean', example: true),
                new OA\Property(property: 'has_unlimited_subscribers', type: 'boolean', example: false),
                new OA\Property(property: 'has_shifts', type: 'boolean', example: false),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الإعدادات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity type settings updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/ActivityTypeResource')
            ]
        )
    )]
    public function updateSettings(\Modules\Sports\Http\Requests\UpdateActivityTypeSettingsRequest $request, ActivityType $activity_type)
    {
        $activity_type->update($request->only([
            'is_session_based',
            'has_unlimited_subscribers',
            'has_shifts',
        ]));

        return $this->successResponse(
            new ActivityTypeResource($activity_type),
            __('Activity type settings updated successfully')
        );
    }
}
