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
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['first_name', 'last_name', 'mobile', 'gender'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', example: 'أحمد'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'محمد'),
                    new OA\Property(property: 'mobile_country_code', type: 'string', example: '+963'),
                    new OA\Property(property: 'mobile', type: 'string', example: '0501234567'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                    new OA\Property(property: 'age', type: 'integer', description: '(مطلوب في حال لم يتم إدخال تاريخ الميلاد) العمر بالسنوات', example: 25),
                    new OA\Property(property: 'dob', type: 'string', format: 'date', description: '(مطلوب في حال لم يتم إدخال العمر) تاريخ الميلاد', example: '1995-10-25'),
                    new OA\Property(property: 'address', type: 'string', nullable: true, example: 'شارع الملك فهد، الرياض'),
                    new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'صورة اللاعب', nullable: true),
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
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من صحة البيانات (مثل اكتمال سعة الخطة، أو عدم تطابق جنس اللاعب مع جنس الفرع المحدد)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'لا يمكن إضافة هذا اللاعب/ة في هذا الفرع بسبب قيود الجنس الخاصة بالفرع.'),
                new OA\Property(property: 'errors', type: 'object')
            ]
        )
    )]
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
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية الأعضاء حسب الفرع، إذا لم يتم إرساله سيتم جلب الأعضاء من جميع الفروع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب الأعضاء بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Members retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'total_subscriptions_amount', type: 'number', format: 'float', example: 1500.00),
                            new OA\Property(property: 'total_paid_amount', type: 'number', format: 'float', example: 1000.00),
                            new OA\Property(
                                property: 'subscriptions',
                                type: 'array',
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 1500.00),
                                        new OA\Property(property: 'paid_amount', type: 'number', format: 'float', example: 1000.00),
                                        new OA\Property(property: 'is_fully_paid', type: 'boolean', example: false)
                                    ]
                                )
                            )
                        ]
                    )
                )
            ]
        )
    )]
    public function index(\Illuminate\Http\Request $request)
    {
        $filters = $request->all();
        $members = $this->memberService->getAllMembers($filters);
        return $this->successResponse($members, __('Members retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/members/stats',
        summary: '📊 إحصائيات الأعضاء',
        description: 'استرجاع إحصائيات الأعضاء (العدد الكلي، النشطين، ذكور/إناث).',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الإحصائيات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member statistics retrieved successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'total_members', type: 'integer'),
                    new OA\Property(property: 'active_members', type: 'integer'),
                    new OA\Property(property: 'male_members', type: 'integer'),
                    new OA\Property(property: 'female_members', type: 'integer'),
                ])
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function stats(\Illuminate\Http\Request $request)
    {
        $filters = $request->only(['branch_id']);
        $stats = $this->memberService->getStats($filters);
        return $this->successResponse($stats, __('Member statistics retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/members/{id}',
        summary: '🔍 عرض بيانات عضو محدد',
        description: 'جلب تفاصيل عضو معين باستخدام المعرف الخاص به.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب بيانات العضو بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'total_subscriptions_amount', type: 'number', format: 'float', example: 1500.00),
                        new OA\Property(property: 'total_paid_amount', type: 'number', format: 'float', example: 1000.00),
                        new OA\Property(
                            property: 'subscriptions',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 1500.00),
                                    new OA\Property(property: 'paid_amount', type: 'number', format: 'float', example: 1000.00),
                                    new OA\Property(property: 'is_fully_paid', type: 'boolean', example: false),
                                    new OA\Property(
                                        property: 'items',
                                        type: 'array',
                                        items: new OA\Items(
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                                new OA\Property(property: 'activity', type: 'object', properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                                    new OA\Property(property: 'name', type: 'string', example: 'سباحة')
                                                ])
                                            ]
                                        )
                                    )
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
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
