<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\MemberManager\Http\Requests\PlayerRegistrationRequest;
use Modules\MemberManager\Services\PlayerRegistrationService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class PlayerRegistrationController extends BaseController
{
    protected $registrationService;
    protected $memberService;

    public function __construct(PlayerRegistrationService $registrationService, \Modules\MemberManager\Services\MemberService $memberService)
    {
        $this->registrationService = $registrationService;
        $this->memberService = $memberService;
    }

    #[OA\Post(
        path: '/v1/members/register',
        summary: '➕ تسجيل لاعب جديد (متدرب)',
        description: 'تسجيل لاعب جديد يشمل إنشاء بياناته الشخصية وعضويته.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['first_name', 'last_name', 'mobile', 'gender', 'age'],
            properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'أحمد'),
                new OA\Property(property: 'last_name', type: 'string', example: 'محمد'),
                new OA\Property(property: 'mobile_country_code', type: 'string', example: '+963'),
                new OA\Property(property: 'mobile', type: 'string', example: '0501234567'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'age', type: 'integer', example: 25),
                new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1995-10-25'),
                new OA\Property(property: 'address', type: 'string', nullable: true, example: 'شارع الملك فهد، الرياض'),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                new OA\Property(
                    property: 'additional_contacts',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: ['name', 'phone_number'],
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'والد اللاعب'),
                            new OA\Property(property: 'country_code', type: 'string', example: '+963'),
                            new OA\Property(property: 'phone_number', type: 'string', example: '0509876543'),
                            new OA\Property(property: 'relation', type: 'string', example: 'Father')
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

    #[OA\Put(
        path: '/v1/members/{id}',
        summary: '📝 تعديل بيانات لاعب (متدرب)',
        description: 'تعديل البيانات الأساسية للاعب مثل الاسم ورقم الجوال والفرع وجهات الاتصال.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو (Member ID)', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'أحمد'),
                new OA\Property(property: 'last_name', type: 'string', example: 'محمد'),
                new OA\Property(property: 'mobile_country_code', type: 'string', example: '+963'),
                new OA\Property(property: 'mobile', type: 'string', example: '0501234567'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1995-10-25'),
                new OA\Property(property: 'address', type: 'string', nullable: true, example: 'شارع الملك فهد، الرياض'),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                new OA\Property(
                    property: 'additional_contacts',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: ['name', 'phone_number'],
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'والد اللاعب'),
                            new OA\Property(property: 'country_code', type: 'string', example: '+963'),
                            new OA\Property(property: 'phone_number', type: 'string', example: '0509876543'),
                            new OA\Property(property: 'relation', type: 'string', example: 'Father')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تعديل بيانات اللاعب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Player updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات')]
    #[OA\Response(response: 404, description: '🚫 اللاعب غير موجود')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function update(\Modules\MemberManager\Http\Requests\UpdatePlayerRequest $request, $id)
    {
        $result = $this->registrationService->updatePlayer($id, $request->validated());

        return $this->successResponse(
            $result,
            __('Player updated successfully'),
            200
        );
    }

    #[OA\Get(
        path: '/v1/members',
        summary: '📋 عرض جميع الأعضاء',
        description: 'جلب قائمة بجميع الأعضاء (المتدربين) مع إمكانية التصفية.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ تم جلب الأعضاء بنجاح')]
    public function index(\Illuminate\Http\Request $request)
    {
        $filters = $request->all();
        $members = $this->memberService->getAllMembers($filters);
        return $this->successResponse($members, __('Members retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/members/{id}',
        summary: '🔍 عرض بيانات عضو محدد',
        description: 'جلب تفاصيل عضو معين باستخدام المعرف الخاص به.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم جلب بيانات العضو بنجاح')]
    #[OA\Response(response: 404, description: '🚫 العضو غير موجود')]
    public function show($id)
    {
        $member = $this->memberService->getMemberById($id);
        if (!$member) {
            return response()->json(['message' => __('Member not found')], 404);
        }
        return $this->successResponse($member, __('Member retrieved successfully'));
    }

    #[OA\Delete(
        path: '/v1/members/{id}',
        summary: '🗑️ حذف عضو',
        description: 'حذف عضو محدد من النظام.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم حذف العضو بنجاح')]
    #[OA\Response(response: 404, description: '🚫 العضو غير موجود')]
    public function destroy($id)
    {
        $deleted = $this->memberService->deleteMember($id);
        if (!$deleted) {
            return response()->json(['message' => __('Member not found or could not be deleted')], 404);
        }
        return $this->successResponse(null, __('Member deleted successfully'));
    }
}
