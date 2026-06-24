<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\MemberManager\Http\Requests\PlayerRegistrationRequest;
use Modules\MemberManager\Services\PlayerRegistrationService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class PlayerRegistrationController extends BaseController
{
    protected $registrationService;

    public function __construct(PlayerRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    #[OA\Post(
        path: '/v1/players/register',
        summary: '➕ تسجيل لاعب جديد (متدرب)',
        description: 'تسجيل لاعب جديد يشمل إنشاء بياناته الشخصية وعضويته وإضافة خطط الاشتراك مع التحقق من سعة الاشتراكات.',
        tags: ['Player Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['first_name', 'last_name', 'mobile', 'gender', 'plans'],
            properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'أحمد'),
                new OA\Property(property: 'last_name', type: 'string', example: 'محمد'),
                new OA\Property(property: 'mobile', type: 'string', example: '0501234567'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1995-10-25'),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                new OA\Property(
                    property: 'additional_contacts',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: ['name', 'phone_number'],
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'والد اللاعب'),
                            new OA\Property(property: 'phone_number', type: 'string', example: '0509876543'),
                            new OA\Property(property: 'relation', type: 'string', example: 'Father')
                        ]
                    )
                ),
                new OA\Property(
                    property: 'plans',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: ['plan_id'],
                        properties: [
                            new OA\Property(property: 'plan_id', type: 'integer', example: 2),
                            new OA\Property(property: 'paid_amount', type: 'number', example: 150.50)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم تسجيل اللاعب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Player registered successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات (مثل اكتمال سعة الخطة)')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function register(PlayerRegistrationRequest $request)
    {
        $result = $this->registrationService->registerPlayer($request->validated());

        return $this->successResponse(
            $result,
            __('Player registered successfully'),
            201
        );
    }
}
