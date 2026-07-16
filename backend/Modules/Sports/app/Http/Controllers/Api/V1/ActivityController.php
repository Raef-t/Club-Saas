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
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الأنشطة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activities retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ActivityResource'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $query = Activity::with('activityType');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);

        }

        if ($request->has('facility_id')) {
            $facility = \Modules\ClubManager\Models\Facility::find($request->facility_id);
            // Gender restrictions for facility are applied at the facility/session level.
        }

        $activities = $query->orderBy('id')->get();
        return $this->successResponse(ActivityResource::collection($activities), __('Activities retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/activities/stats',
        summary: '📊 إحصائيات الأنشطة',
        description: 'استرجاع إحصائيات الأنشطة (العدد الكلي والأنشطة المجمعة حسب النوع).',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الإحصائيات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity statistics retrieved successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'total_activities', type: 'integer'),
                    new OA\Property(property: 'activities_by_type', type: 'array', items: new OA\Items(type: 'object'))
                ])
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function stats(Request $request)
    {
        $query = Activity::query();
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $totalActivities = (clone $query)->count();

        // Get count grouped by activity_type_id
        // Using Eloquent with to load the ActivityType name
        $grouped = (clone $query)
            ->select('activity_type_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('activity_type_id')
            ->with('activityType')
            ->get();

        $activitiesByType = $grouped->map(function ($item) {
            return [
                'activity_type_id' => $item->activity_type_id,
                'activity_type_name' => $item->activityType ? $item->activityType->name : 'Unknown',
                'count' => $item->count,
            ];
        });

        return $this->successResponse([
            'total_activities' => $totalActivities,
            'activities_by_type' => $activitiesByType,
        ], __('Activity statistics retrieved successfully'));
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
            required: ['name', 'activity_type_id', 'branch_id'],
            properties: [
                new OA\Property(property: 'branch_id', description: '(مطلوب) معرف الفرع', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', description: 'اسم النشاط (مطلوب)', example: 'يوغا'),
                new OA\Property(property: 'description', type: 'string', description: '(اختياري) وصف النشاط', example: 'جلسة يوغا للمبتدئين'),
                new OA\Property(property: 'activity_type_id', description: '(مطلوب) معرف نوع النشاط', type: 'integer', example: 1),
                new OA\Property(property: 'is_active', type: 'boolean', example: true)
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
                new OA\Property(property: 'data', ref: '#/components/schemas/ActivityResource')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreActivityRequest $request)
    {
        $data = $request->validated();

        $activity = Activity::create($data);
        $activity->load('activityType');
        return $this->successResponse(new ActivityResource($activity), __('Activity created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/activities/{activity}',
        summary: '🔍 تفاصيل النشاط',
        description: 'استرجاع تفاصيل نشاط رياضي محدد.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'activity', in: 'path', required: true, description: 'معرف النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل النشاط',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/ActivityResource')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 النشاط غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show(int $id)
    {
        $activity = Activity::with('activityType')->findOrFail($id);
        return $this->successResponse(new ActivityResource($activity), __('Activity retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/activities/{activity}',
        summary: '✏️ تعديل النشاط',
        description: 'تحديث بيانات نشاط رياضي مسجل.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'activity', in: 'path', required: true, description: 'معرف النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'branch_id', description: '(مطلوب) معرف الفرع', type: 'integer', example: 2),
                new OA\Property(property: 'name', type: 'string', description: 'اسم النشاط', example: 'اجهزة عام'),
                new OA\Property(property: 'description', type: 'string', description: '(اختياري) وصف النشاط', example: 'جلسة يوغا للمبتدئين'),
                new OA\Property(property: 'activity_type_id', description: '(اختياري) معرف نوع النشاط', type: 'integer', example: 4),
                new OA\Property(property: 'is_active', type: 'boolean', example: true)
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
                new OA\Property(property: 'data', ref: '#/components/schemas/ActivityResource')
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
        $activity->load('activityType');
        return $this->successResponse(new ActivityResource($activity), __('Activity updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/activities/{activity}',
        summary: '🗑️ حذف النشاط',
        description: 'إزالة نشاط رياضي من النظام. لا يمكن حذف النشاط إذا كان مرتبطاً بخطة اشتراك أو مدرب.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'activity', in: 'path', required: true, description: 'معرف النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
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
    #[OA\Response(
        response: 409, 
        description: '🚫 لا يمكن الحذف — النشاط مرتبط ببيانات أخرى', 
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'), 
                new OA\Property(property: 'message', type: 'string', example: 'لا يمكن حذف هذا النشاط لأنه مرتبط بـ 2 خطة اشتراك، و 1 مدرب. يمكنك تعطيل النشاط (is_active = false) بدلاً من حذفه.')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 النشاط غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(int $id)
    {
        $activity = Activity::findOrFail($id);

        // Check if activity is linked to any subscription plan (via staff_activities → plan_activities)
        $planActivitiesCount = \Modules\Sports\Models\StaffActivity::where('activity_id', $id)
            ->whereHas('planActivities')
            ->count();

        // Check if activity is linked to any staff (coach assignment)
        $staffActivitiesCount = \Modules\Sports\Models\StaffActivity::where('activity_id', $id)->count();

        // Check if activity is linked to any player subscription items
        $subscriptionItemsCount = \Modules\SubscriptionManager\Models\PlayerSubscriptionItem::where('activity_id', $id)->count();

        $blocked = [];

        if ($planActivitiesCount > 0) {
            $blocked[] = "مرتبط بـ {$planActivitiesCount} " . ($planActivitiesCount === 1 ? 'خطة اشتراك' : 'خطط اشتراك');
        }
        if ($staffActivitiesCount > 0) {
            $blocked[] = "مرتبط بـ {$staffActivitiesCount} " . ($staffActivitiesCount === 1 ? 'مدرب' : 'مدربين');
        }
        if ($subscriptionItemsCount > 0) {
            $blocked[] = "مرتبط بـ {$subscriptionItemsCount} " . ($subscriptionItemsCount === 1 ? 'اشتراك عضو' : 'اشتراكات أعضاء');
        }

        if (!empty($blocked)) {
            $reasons = implode('، و', $blocked);
            return $this->errorResponse(
                "لا يمكن حذف هذا النشاط لأنه {$reasons}. يمكنك تعطيل النشاط (is_active = false) بدلاً من حذفه.",
                409
            );
        }

        $activity->delete();
        return $this->successResponse(null, __('Activity deleted successfully'));
    }
}
