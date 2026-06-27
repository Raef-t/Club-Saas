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
        path: '/v1/player/health-profile',
        summary: '🏥 جلب الملف الصحي للاعب',
        description: 'استرجاع الملف الصحي للاعب المسجل دخوله حالياً.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الملف الصحي بنجاح')]
    #[OA\Response(response: 403, description: '🚫 لا يوجد حساب لاعب مرتبط')]
    public function getHealthProfile(Request $request)
    {
        $memberId = $this->getMemberId($request);
        if (!$memberId) {
            return $this->errorResponse(__('No player account linked to this user.'), 403);
        }

        $profile = $this->memberService->getHealthProfile($memberId);
        return $this->successResponse($profile, __('Health profile retrieved'));
    }

    #[OA\Put(
        path: '/v1/player/health-profile',
        summary: '📝 تحديث الملف الصحي للاعب',
        description: 'تحديث أو إنشاء الملف الصحي للاعب المسجل دخوله حالياً.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'allergies', type: 'string', example: 'حساسية من البنسلين'),
                new OA\Property(property: 'blood_type', type: 'string', example: 'O+'),
                new OA\Property(property: 'sport_goal', type: 'string', example: 'خسارة الوزن')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم التحديث بنجاح')]
    public function updateHealthProfile(UpdatePlayerHealthProfileRequest $request)
    {
        $memberId = $this->getMemberId($request);
        if (!$memberId) {
            return $this->errorResponse(__('No player account linked to this user.'), 403);
        }

        // We can reuse updateMember from MemberService which handles health_profile creation/updating
        // Or we can just do it directly. Let's use updateMember.
        $this->memberService->updateMember($memberId, [
            'health_profile' => $request->validated()
        ]);

        $profile = $this->memberService->getHealthProfile($memberId);
        return $this->successResponse($profile, __('Health profile updated successfully'));
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
        path: '/v1/player/health-profiles',
        summary: '🏥 عرض جميع السجلات الصحية للاعبين',
        description: 'استرجاع جميع السجلات الصحية لجميع اللاعبين.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ تم الاسترجاع بنجاح')]
    public function getAllHealthProfiles()
    {
        if (!request()->user() || !request()->user()->hasRole('super_admin')) {
            return $this->errorResponse(__('Unauthorized'), 403);
        }
        $profiles = \Modules\MemberManager\Models\MemberHealthProfile::with('member')->get();
        return $this->successResponse($profiles, __('All health profiles retrieved successfully'));
    }

    #[OA\Delete(
        path: '/v1/player/health-profiles/{id}',
        summary: '🗑️ حذف سجل صحي للاعب',
        description: 'حذف سجل صحي معين بواسطة المعرف.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل الصحي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم الحذف بنجاح')]
    public function deleteHealthProfile($id)
    {
        if (!request()->user() || !request()->user()->hasRole('super_admin')) {
            return $this->errorResponse(__('Unauthorized'), 403);
        }
        $profile = \Modules\MemberManager\Models\MemberHealthProfile::findOrFail($id);
        $profile->delete();
        return $this->successResponse(null, __('Health profile deleted successfully'));
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
