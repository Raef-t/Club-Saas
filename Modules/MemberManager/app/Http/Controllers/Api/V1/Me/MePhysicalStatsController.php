<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1\Me;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\MemberManager\Http\Requests\Api\V1\Me\UpdatePhysicalStatsRequest;
use Modules\MemberManager\Services\Me\MePhysicalStatsService;
use OpenApi\Attributes as OA;

class MePhysicalStatsController extends BaseController
{
    protected $service;

    public function __construct(MePhysicalStatsService $service)
    {
        $this->service = $service;
    }

    #[OA\Post(
        path: '/v1/me/physical-stats',
        summary: '💪 تحديث البيانات الجسدية (الطول، الوزن، العمر)',
        description: 'يسمح للاعب بتحديث طوله، وزنه، وتاريخ ميلاده. يقوم النظام بحساب العمر تلقائياً وإضافة سجل جديد في قياسات العضو.',
        tags: ['Member App'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'height', type: 'number', format: 'float', description: 'الطول بالسنتيمتر', example: 175.5),
                new OA\Property(property: 'weight', type: 'number', format: 'float', description: 'الوزن بالكيلوجرام', example: 70.2),
                new OA\Property(property: 'dob', type: 'string', format: 'date', description: 'تاريخ الميلاد (YYYY-MM-DD)', example: '1995-05-20')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التحديث بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم تحديث البيانات الجسدية بنجاح.'),
                new OA\Property(property: 'data', type: 'object', nullable: true)
            ]
        )
    )]
    #[OA\Response(response: 403, description: '🚫 الملف الشخصي غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member profile not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdatePhysicalStatsRequest $request)
    {
        try {
            $this->service->updateStats($request->user(), $request->validated());
            return $this->successResponse(null, __('تم تحديث البيانات الجسدية بنجاح'));
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === __('Member profile not found.') ? 403 : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }
}
