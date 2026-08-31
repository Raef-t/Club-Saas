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
        description: 'استرجاع تقرير تفصيلي باللاعبين المتأثرين، أيام التمديد المحسوبة لكل لاعب، والجلسات التي سيتم إلغاؤها.',
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
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
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
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
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
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (الافتراضي: 15)', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب سجل الإيقافات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Plan suspensions retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    public function index(\Illuminate\Http\Request $request, int $id)
    {
        $perPage = $this->getPerPage($request);
        $suspensions = $this->suspensionService->getSuspensions($id, $perPage);
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
    #[OA\Parameter(name: 'suspensionId', in: 'path', required: true, description: 'معرف سجل الإيقاف', schema: new OA\Schema(type: 'integer', example: 5))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استئناف الفعالية وإلغاء الإيقاف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription plan suspension lifted successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    public function destroy(int $id, int $suspensionId)
    {
        $suspension = $this->suspensionService->liftSuspension($id, $suspensionId);
        return $this->successResponse($suspension, __('Subscription plan suspension lifted successfully'));
    }
}
