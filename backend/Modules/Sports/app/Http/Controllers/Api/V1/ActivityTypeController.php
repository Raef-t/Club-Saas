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
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
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
                            new OA\Property(property: 'name', type: 'string', example: 'أنشطة لياقة وكمال أجسام'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'جميع التمارين الرياضية واللياقة البدنية'),
                            new OA\Property(property: 'is_session_based', type: 'boolean', example: false),
                            new OA\Property(property: 'has_unlimited_subscribers', type: 'boolean', example: true),
                            new OA\Property(property: 'has_shifts', type: 'boolean', example: false),
                            new OA\Property(property: 'is_daily_entry', type: 'boolean', example: true),
                            new OA\Property(property: 'created_at', type: 'string', example: '2026-01-15T10:00:00.000000Z'),
                            new OA\Property(property: 'updated_at', type: 'string', example: '2026-01-15T10:00:00.000000Z')
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

        if ($request->has('per_page') && $request->input('per_page') !== 'all') {
            $perPage = min(max((int) $request->input('per_page'), 1), 100);
            $types = $query->paginate($perPage);
        } else {
            $types = $query->get();
        }

        return $this->successResponse(
            ActivityTypeResource::collection($types),
            __('Activity types retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/activity-types',
        summary: '➕ إضافة نوع نشاط جديد',
        description: 'إنشاء نوع نشاط رياضي جديد وتحديد إعداداته التشغيلية.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'اسم نوع النشاط (مطلوب)', example: 'سباحة وألعاب مائية'),
                new OA\Property(property: 'description', type: 'string', nullable: true, description: 'وصف نوع النشاط', example: 'تمارين وتدريبات السباحة بجميع أنواعها'),
                new OA\Property(property: 'is_session_based', type: 'boolean', description: 'هل يعتمد على حصص/جلسات محددة', example: true),
                new OA\Property(property: 'has_unlimited_subscribers', type: 'boolean', description: 'هل عدد المشتركين غير محدود', example: false),
                new OA\Property(property: 'has_shifts', type: 'boolean', description: 'هل يعتمد على ورديات', example: true),
                new OA\Property(property: 'is_daily_entry', type: 'boolean', description: 'هل يسمح بالدخول اليومي', example: false)
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 2),
                        new OA\Property(property: 'name', type: 'string', example: 'سباحة وألعاب مائية'),
                        new OA\Property(property: 'description', type: 'string', example: 'تمارين وتدريبات السباحة بجميع أنواعها'),
                        new OA\Property(property: 'is_session_based', type: 'boolean', example: true),
                        new OA\Property(property: 'has_unlimited_subscribers', type: 'boolean', example: false),
                        new OA\Property(property: 'has_shifts', type: 'boolean', example: true),
                        new OA\Property(property: 'is_daily_entry', type: 'boolean', example: false),
                        new OA\Property(property: 'created_at', type: 'string', example: '2026-09-06T12:00:00.000000Z'),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2026-09-06T12:00:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'), new OA\Property(property: 'errors', type: 'object')]))]
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
        summary: '🔍 تفاصيل نوع النشاط',
        description: 'استرجاع تفاصيل نوع نشاط رياضي محدد.',
        tags: ['Sports & Activities'],
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'أنشطة لياقة وكمال أجسام'),
                        new OA\Property(property: 'description', type: 'string', example: 'جميع التمارين الرياضية واللياقة البدنية'),
                        new OA\Property(property: 'is_session_based', type: 'boolean', example: false),
                        new OA\Property(property: 'has_unlimited_subscribers', type: 'boolean', example: true),
                        new OA\Property(property: 'has_shifts', type: 'boolean', example: false),
                        new OA\Property(property: 'is_daily_entry', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', example: '2026-01-15T10:00:00.000000Z'),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2026-01-15T10:00:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 نوع النشاط غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
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
        summary: '✏️ تعديل نوع النشاط',
        description: 'تحديث بيانات وإعدادات نوع نشاط موجود.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'activity_type', in: 'path', required: true, description: 'معرف نوع النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'أنشطة اللياقة البدنية والكمال الجسماني'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'تمارين وتدريبات حديد وصالة رياضية'),
                new OA\Property(property: 'is_session_based', type: 'boolean', example: false),
                new OA\Property(property: 'has_unlimited_subscribers', type: 'boolean', example: true),
                new OA\Property(property: 'has_shifts', type: 'boolean', example: false),
                new OA\Property(property: 'is_daily_entry', type: 'boolean', example: true)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث نوع النشاط بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity type updated successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'أنشطة اللياقة البدنية والكمال الجسماني'),
                        new OA\Property(property: 'description', type: 'string', example: 'تمارين وتدريبات حديد وصالة رياضية'),
                        new OA\Property(property: 'is_session_based', type: 'boolean', example: false),
                        new OA\Property(property: 'has_unlimited_subscribers', type: 'boolean', example: true),
                        new OA\Property(property: 'has_shifts', type: 'boolean', example: false),
                        new OA\Property(property: 'is_daily_entry', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', example: '2026-01-15T10:00:00.000000Z'),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2026-09-06T12:15:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 نوع النشاط غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.')]))]
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
        description: 'حذف نوع نشاط من النظام. يلزم تأكيد الحذف بطلب "delete" في حال وجود اشتراكات سابقة أو أنشطة تابعة.',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'activity_type', in: 'path', required: true, description: 'معرف نوع النشاط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'confirm', in: 'query', required: false, description: 'كلمة التأكيد (delete) لإتمام عملية الحذف', schema: new OA\Schema(type: 'string', example: 'delete'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف نوع النشاط بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activity type deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(
        response: 422, 
        description: '⚠️ يتطلب تأكيد الحذف بإرسال كلمة delete', 
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'), 
                new OA\Property(property: 'message', type: 'string', example: 'تنبيه: يوجد 3 اشتراك(ات) نشطة حالية للأنشطة التي تندرج تحت نوع النشاط هذا. هل أنت متأكد؟ أرسل "delete" للتأكيد.')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 نوع النشاط غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(\Illuminate\Http\Request $request, ActivityType $activity_type)
    {
        // 1. Check for active subscriptions referencing any activity of this type
        $activeSubsCount = 0;
        if (class_exists(\Modules\SubscriptionManager\Models\PlayerSubscriptionItem::class)) {
            $activeSubsCount = \Modules\SubscriptionManager\Models\PlayerSubscriptionItem::whereHas('activity', function ($query) use ($activity_type) {
                $query->where('activity_type_id', $activity_type->id);
            })->whereHas('subscription', function ($query) {
                $query->where('status', \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE->value);
            })->count();
        }

        // 2. Validate confirmation string
        $confirm = strtolower(trim($request->input('confirm') ?? $request->input('confirmation') ?? $request->input('confirm_text') ?? ''));

        if ($confirm !== 'delete') {
            if ($activeSubsCount > 0) {
                return $this->errorResponse(
                    __('تنبيه: يوجد :count اشتراك(ات) نشطة حالية للأنشطة التي تندرج تحت نوع النشاط هذا. حذف نوع النشاط سيؤدي إلى إيقافها وإلغاء إمكانية حضورهم لها. هل أنت متأكد؟ أرسل "delete" للتأكيد.', ['count' => $activeSubsCount]),
                    422
                );
            }

            return $this->errorResponse(
                __('سيتم حذف نوع النشاط هذا وكافة الأنشطة والاشتراكات المنتهية التابعة له، هل أنت متأكد؟ أرسل "delete" للتأكيد.'),
                422
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
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'activity_type', description: 'معرف نوع النشاط', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'is_session_based', type: 'boolean', example: true),
                new OA\Property(property: 'has_unlimited_subscribers', type: 'boolean', example: false),
                new OA\Property(property: 'has_shifts', type: 'boolean', example: false),
                new OA\Property(property: 'is_daily_entry', type: 'boolean', example: false)
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'أنشطة لياقة وكمال أجسام'),
                        new OA\Property(property: 'description', type: 'string', example: 'جميع التمارين الرياضية واللياقة البدنية'),
                        new OA\Property(property: 'is_session_based', type: 'boolean', example: true),
                        new OA\Property(property: 'has_unlimited_subscribers', type: 'boolean', example: false),
                        new OA\Property(property: 'has_shifts', type: 'boolean', example: false),
                        new OA\Property(property: 'is_daily_entry', type: 'boolean', example: false),
                        new OA\Property(property: 'created_at', type: 'string', example: '2026-01-15T10:00:00.000000Z'),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2026-09-06T12:20:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 نوع النشاط غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function updateSettings(\Modules\Sports\Http\Requests\UpdateActivityTypeSettingsRequest $request, ActivityType $activity_type)
    {
        $activity_type->update($request->validated());

        return $this->successResponse(
            new ActivityTypeResource($activity_type),
            __('Activity type settings updated successfully')
        );
    }
}
