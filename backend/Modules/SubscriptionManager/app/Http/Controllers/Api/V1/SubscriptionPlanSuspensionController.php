<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Http\Requests\PreviewPlanSuspensionRequest;
use Modules\SubscriptionManager\Http\Requests\SuspendPlanRequest;
use Modules\SubscriptionManager\Services\SubscriptionPlanSuspensionService;
use OpenApi\Attributes as OA;

class SubscriptionPlanSuspensionController extends BaseController
{
    public function __construct(
        protected SubscriptionPlanSuspensionService $suspensionService
    ) {}

    #[OA\Post(
        path: '/v1/subscription-plans/{id}/suspensions/preview',
        summary: '🔍 معاينة المتأثرين وتمديد الأيام قبل إيقاف الفعالية',
        description: 'استرجاع تقرير تفصيلي باللاعبين المتأثرين، أيام التمديد المحسوبة لكل لاعب، والجلسات التي سيتم إلغاؤها قبل اعتماد الإيقاف.',
        tags: ['Subscription Plan Suspensions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفعالية (Subscription Plan ID)', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['suspend_start_date', 'suspend_end_date'],
            properties: [
                new OA\Property(property: 'suspend_start_date', type: 'string', format: 'date', example: '2026-08-15', description: 'تاريخ بداية الإيقاف'),
                new OA\Property(property: 'suspend_end_date', type: 'string', format: 'date', example: '2026-08-22', description: 'تاريخ نهاية الإيقاف'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تقرير المعاينة التفصيلي',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Suspension preview generated successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'plan_id', type: 'integer', example: 1),
                        new OA\Property(property: 'plan_name', type: 'string', example: 'اشتراك السباحة للأطفال'),
                        new OA\Property(property: 'suspend_start_date', type: 'string', example: '2026-08-15'),
                        new OA\Property(property: 'suspend_end_date', type: 'string', example: '2026-08-22'),
                        new OA\Property(property: 'total_affected_members', type: 'integer', example: 12),
                        new OA\Property(
                            property: 'affected_members',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'member_id', type: 'integer', example: 5),
                                    new OA\Property(property: 'member_name', type: 'string', example: 'عبد الله السالم'),
                                    new OA\Property(property: 'current_end_date', type: 'string', example: '2026-09-01'),
                                    new OA\Property(property: 'new_end_date', type: 'string', example: '2026-09-08'),
                                    new OA\Property(property: 'extended_days', type: 'integer', example: 7)
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة التواريخ', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.')]))]
    #[OA\Response(response: 404, description: '🚫 الفعالية غير موجودة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function preview(PreviewPlanSuspensionRequest $request, int $id)
    {
        $validated = $request->validated();
        $previewData = $this->suspensionService->preview(
            $id,
            $validated['suspend_start_date'],
            $validated['suspend_end_date']
        );

        return $this->successResponse($previewData, __('Suspension preview generated successfully'));
    }

    #[OA\Post(
        path: '/v1/subscription-plans/{id}/suspend',
        summary: '⏸️ إيقاف الفعالية مؤقتاً واعتذار الكوتش',
        description: 'إيقاف الفعالية بين تاريخين محددين، تجميد وتمديد اشتراكات اللاعبين المتأثرين، إلغاء الحصص المجدولة، وإرسال الإشعارات.',
        tags: ['Subscription Plan Suspensions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفعالية (Subscription Plan ID)', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['suspend_start_date', 'suspend_end_date'],
            properties: [
                new OA\Property(property: 'suspend_start_date', type: 'string', format: 'date', example: '2026-08-15', description: 'تاريخ بداية الإيقاف'),
                new OA\Property(property: 'suspend_end_date', type: 'string', format: 'date', example: '2026-08-22', description: 'تاريخ نهاية الإيقاف'),
                new OA\Property(property: 'reason', type: 'string', example: 'ظرف صحي طارئ للكوتش', description: 'سبب الإيقاف أو الاعتذار'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم إيقاف الفعالية وتجميد وتمديد الاشتراكات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription plan suspended successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 10),
                        new OA\Property(property: 'subscription_plan_id', type: 'integer', example: 1),
                        new OA\Property(property: 'suspend_start_date', type: 'string', example: '2026-08-15'),
                        new OA\Property(property: 'suspend_end_date', type: 'string', example: '2026-08-22'),
                        new OA\Property(property: 'reason', type: 'string', example: 'ظرف صحي طارئ للكوتش'),
                        new OA\Property(property: 'created_by', type: 'integer', example: 3),
                        new OA\Property(property: 'status', type: 'string', example: 'active'),
                        new OA\Property(property: 'created_at', type: 'string', example: '2026-09-06T12:35:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في البيانات المدخلة', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.')]))]
    #[OA\Response(response: 404, description: '🚫 الفعالية غير موجودة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function suspend(SuspendPlanRequest $request, int $id)
    {
        $userId = auth('sanctum')->id();
        $suspension = $this->suspensionService->suspend($id, $request->validated(), $userId);

        return $this->successResponse($suspension, __('Subscription plan suspended successfully'));
    }

    #[OA\Get(
        path: '/v1/subscription-plans/{id}/suspensions',
        summary: '📋 عرض سجل إيقافات الفعالية',
        description: 'جلب قائمة بكافة الإيقافات السابقة والحالية والمجدولة لهذه الفعالية.',
        tags: ['Subscription Plan Suspensions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفعالية', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب سجل الإيقافات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Plan suspensions retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 10),
                            new OA\Property(property: 'subscription_plan_id', type: 'integer', example: 1),
                            new OA\Property(property: 'suspend_start_date', type: 'string', example: '2026-08-15'),
                            new OA\Property(property: 'suspend_end_date', type: 'string', example: '2026-08-22'),
                            new OA\Property(property: 'reason', type: 'string', example: 'ظرف صحي طارئ للكوتش'),
                            new OA\Property(property: 'status', type: 'string', example: 'active'),
                            new OA\Property(property: 'created_at', type: 'string', example: '2026-09-06T12:35:00.000000Z')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request, int $id)
    {
        $suspensions = $this->suspensionService->getSuspensions($id, $request->all());
        return $this->successResponse($suspensions, __('Plan suspensions retrieved successfully'));
    }

    #[OA\Delete(
        path: '/v1/subscription-plans/{id}/suspensions/{suspensionId}',
        summary: '▶️ استئناف الفعالية / إلغاء الإيقاف مبكراً',
        description: 'رفع الإيقاف عن الفعالية مبكراً، فك تجميد اشتراكات اللاعبين، خصم الأيام غير المستخدمة والاحتفاظ بالأيام المستفادة فقط، واستعادة الجلسات المتبقية.',
        tags: ['Subscription Plan Suspensions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفعالية', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'suspensionId', in: 'path', required: true, description: 'معرف سجل الإيقاف', schema: new OA\Schema(type: 'integer', example: 10))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استئناف الفعالية وإلغاء الإيقاف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription plan suspension lifted successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 10),
                        new OA\Property(property: 'subscription_plan_id', type: 'integer', example: 1),
                        new OA\Property(property: 'status', type: 'string', example: 'lifted_early'),
                        new OA\Property(property: 'lifted_at', type: 'string', example: '2026-09-06T12:35:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 سجل الإيقاف غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(int $id, int $suspensionId)
    {
        $suspension = $this->suspensionService->liftSuspension($id, $suspensionId);
        return $this->successResponse($suspension, __('Subscription plan suspension lifted successfully'));
    }
}
