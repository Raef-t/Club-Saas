<?php

namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Sports\Models\Activity;
use Modules\Sports\Http\Resources\ActivityResource;
use Modules\ClubManager\Models\Facility;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Modules\Sports\Http\Requests\StoreActivityRequest;
use Modules\Sports\Http\Requests\UpdateActivityRequest;

class ActivityController extends BaseController
{
    #[OA\Get(
        path: '/v1/activities',
        summary: '🏋️ عرض جميع الأنشطة الرياضية',
        description: 'استرجاع قائمة بجميع الأنشطة (مثال: سباحة، حديد، يوجا).',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الأنشطة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activities retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $query = Activity::query();

        if ($request->has('facility_id')) {
            $facility = Facility::find($request->facility_id);
            if ($facility && $facility->gender_restriction !== 'mixed') {
                $query->whereIn('gender_allowed', [$facility->gender_restriction, 'mixed']);
            }
        } elseif ($request->has('gender_allowed')) {
            $query->where('gender_allowed', $request->gender_allowed);
        }

        $activities = $query->orderBy('id')->get();
        return $this->successResponse(ActivityResource::collection($activities), __('Activities retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/activities',
        summary: '➕ إضافة نشاط رياضي',
        description: 'إنشاء نشاط رياضي جديد.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name_en', 'name_ar', 'type', 'gender_allowed'],
            properties: [
                new OA\Property(property: 'name_en', type: 'string', example: 'Swimming'),
                new OA\Property(property: 'name_ar', type: 'string', example: 'سباحة'),
                new OA\Property(property: 'type', type: 'string', example: 'Group'),
                new OA\Property(property: 'gender_allowed', type: 'string', example: 'mixed')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء النشاط بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreActivityRequest $request)
    {
        $data = $request->validated();

        $activity = Activity::create($data);
        return $this->successResponse(new ActivityResource($activity), __('Activity created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/activities/{id}',
        summary: '🔍 تفاصيل النشاط',
        description: 'استرجاع تفاصيل نشاط رياضي محدد.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل النشاط',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 النشاط غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show(int $id)
    {
        $activity = Activity::findOrFail($id);
        return $this->successResponse(new ActivityResource($activity), __('Activity retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/activities/{id}',
        summary: '✏️ تعديل النشاط',
        description: 'تحديث بيانات نشاط رياضي مسجل.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name_en', type: 'string', example: 'Advanced Swimming')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التعديل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 النشاط غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateActivityRequest $request, int $id)
    {
        $data = $request->validated();

        $activity = Activity::findOrFail($id);
        $activity->update($data);
        return $this->successResponse(new ActivityResource($activity), __('Activity updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/activities/{id}',
        summary: '🗑️ حذف النشاط',
        description: 'إزالة نشاط رياضي من النظام.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 النشاط غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(int $id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();
        return $this->successResponse(null, __('Activity deleted successfully'));
    }
}
