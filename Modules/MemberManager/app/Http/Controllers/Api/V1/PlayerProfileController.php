<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\MemberManager\Services\MemberService;
use Modules\MemberManager\Http\Requests\UpdatePlayerHealthProfileRequest;
use Modules\MemberManager\Http\Requests\AddPlayerMeasurementRequest;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PlayerProfileController extends BaseController
{
    protected $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    private function getMemberId(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->person || !$user->person->member) {
            return null;
        }
        return $user->person->member->id;
    }


    #[OA\Get(
        path: '/v1/player/measurements',
        summary: '📏 سجل قياسات اللاعب',
        description: 'جلب جميع القياسات السابقة للاعب.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ تم استرجاع القياسات بنجاح')]
    public function getMeasurements(Request $request)
    {
        $memberId = $this->getMemberId($request);
        if (!$memberId) {
            return $this->errorResponse(__('No player account linked to this user.'), 403);
        }

        $measurements = $this->memberService->getMeasurements($memberId);
        return $this->successResponse($measurements, __('Measurements retrieved'));
    }

    #[OA\Post(
        path: '/v1/player/measurements',
        summary: '⚖️ إضافة قياس جديد',
        description: 'تسجيل قياس جديد للاعب.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['weight'],
            properties: [
                new OA\Property(property: 'weight', type: 'number', example: 75.5),
                new OA\Property(property: 'height', type: 'number', example: 178),
                new OA\Property(property: 'neck_circumference', type: 'number', example: 40),
                new OA\Property(property: 'shoulder_circumference', type: 'number', example: 110),
                new OA\Property(property: 'chest_circumference', type: 'number', example: 100),
                new OA\Property(property: 'waist_circumference', type: 'number', example: 85),
                new OA\Property(property: 'hip_circumference', type: 'number', example: 95),
                new OA\Property(property: 'buttocks_circumference', type: 'number', example: 98),
                new OA\Property(property: 'right_thigh_mid', type: 'number', example: 60),
                new OA\Property(property: 'left_thigh', type: 'number', example: 60),
                new OA\Property(property: 'above_right_knee', type: 'number', example: 40),
                new OA\Property(property: 'above_left_knee', type: 'number', example: 40),
                new OA\Property(property: 'right_calf', type: 'number', example: 38),
                new OA\Property(property: 'left_calf', type: 'number', example: 38),
                new OA\Property(property: 'right_bicep', type: 'number', example: 35),
                new OA\Property(property: 'left_bicep', type: 'number', example: 35),
                new OA\Property(property: 'arm_circumference', type: 'number', example: 35),
                new OA\Property(property: 'measurement_date', type: 'string', format: 'date', example: '2023-10-01')
            ]
        )
    )]
    #[OA\Response(response: 201, description: '✅ تم إضافة القياس بنجاح')]
    public function addMeasurement(AddPlayerMeasurementRequest $request)
    {
        $memberId = $this->getMemberId($request);
        if (!$memberId) {
            return $this->errorResponse(__('No player account linked to this user.'), 403);
        }

        $data = $request->validated();
        if (!isset($data['measurement_date'])) {
            $data['measurement_date'] = now();
        }

        $measurement = $this->memberService->recordMeasurement($memberId, $data);
        return $this->successResponse($measurement, __('Measurement added successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/player/all-measurements',
        summary: '📏 عرض جميع قياسات اللاعبين',
        description: 'استرجاع جميع سجلات القياسات لجميع اللاعبين.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ تم الاسترجاع بنجاح')]
    public function getAllMeasurements()
    {
        if (!request()->user() || !request()->user()->hasRole('super_admin')) {
            return $this->errorResponse(__('Unauthorized'), 403);
        }
        $measurements = \Modules\MemberManager\Models\MemberMeasurement::with('member')->get();
        return $this->successResponse($measurements, __('All measurements retrieved successfully'));
    }

    #[OA\Delete(
        path: '/v1/player/measurements/{id}',
        summary: '🗑️ حذف سجل قياس للاعب',
        description: 'حذف سجل قياس معين بواسطة المعرف.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف سجل القياس', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم الحذف بنجاح')]
    public function deleteMeasurement($id)
    {
        if (!request()->user() || !request()->user()->hasRole('super_admin')) {
            return $this->errorResponse(__('Unauthorized'), 403);
        }
        $measurement = \Modules\MemberManager\Models\MemberMeasurement::findOrFail($id);
        $measurement->delete();
        return $this->successResponse(null, __('Measurement deleted successfully'));
    }
}
