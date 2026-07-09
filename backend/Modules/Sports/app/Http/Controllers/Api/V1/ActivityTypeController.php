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

    public function destroy(ActivityType $activity_type)
    {
        $activity_type->delete();
        return $this->successResponse(null, __('Activity type deleted successfully'));
    }
}
