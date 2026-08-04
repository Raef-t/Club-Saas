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
                    new OA\Property(property: 'age', type: 'integer', description: 'العمر بالسنوات (اختياري)', example: 25),
                    new OA\Property(property: 'dob', type: 'string', format: 'date', description: 'تاريخ الميلاد (اختياري)', example: '1995-10-25'),
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
                new OA\Property(property: 'data', ref: '#/components/schemas/MemberResource')
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
            new \Modules\MemberManager\Http\Resources\MemberResource($result['member']),
            __('Player registered successfully'),
            201
        );
    }

    #[OA\Put(
        path: '/v1/members/{id}',
        summary: '📝 تعديل بيانات لاعب (متدرب)',
        description: 'تعديل البيانات الأساسية للاعب مثل الاسم ورقم الجوال والفرع وجهات الاتصال. لتحديث الصورة استخدم endpoint منفصل: POST /v1/members/{id}/photo',
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
                new OA\Property(property: 'age', type: 'integer', description: 'العمر بالسنوات (اختياري)', example: 25),
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
                new OA\Property(property: 'data', ref: '#/components/schemas/MemberResource')
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
            new \Modules\MemberManager\Http\Resources\MemberResource($result),
            __('Player updated successfully'),
            200
        );
    }

    #[OA\Post(
        path: '/v1/members/{id}/photo',
        summary: '🖼️ تحديث صورة العضو',
        description: 'رفع أو تحديث صورة الملف الشخصي للعضو (اللاعب). يجب استخدام هذا الـ endpoint المخصص بدلاً من إرسال الصورة ضمن طلب التعديل العام.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['photo'],
                properties: [
                    new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'صورة العضو')
                ]
            )
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم تحديث الصورة بنجاح', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string', example: 'Member photo updated successfully'), new OA\Property(property: 'data', ref: '#/components/schemas/MemberResource')]))]
    #[OA\Response(response: 404, description: '🚫 العضو غير موجود')]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function updatePhoto(\Modules\MemberManager\Http\Requests\UpdateMemberPhotoRequest $request, $id)
    {
        $result = $this->registrationService->updateMemberPhoto($id, $request->file('photo'));

        return $this->successResponse(
            new \Modules\MemberManager\Http\Resources\MemberResource($result),
            __('Member photo updated successfully'),
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
    #[OA\Parameter(name: 'gender', in: 'query', required: false, description: 'تصفية حسب الجنس', schema: new OA\Schema(type: 'string', enum: ['male', 'female']))]
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'تصفية حسب حالة العضوية', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'frozen']))]
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
                    items: new OA\Items(ref: '#/components/schemas/MemberResource')
                )
            ]
        )
    )]
    public function index(\Illuminate\Http\Request $request)
    {
        $filters = $request->all();
        $members = $this->memberService->getAllMembers($filters);
        return $this->successResponse(\Modules\MemberManager\Http\Resources\MemberResource::collection($members), __('Members retrieved successfully'));
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
                    new OA\Property(property: 'total_subscribed_members', type: 'integer'),
                    new OA\Property(property: 'new_members_this_month', type: 'integer'),
                    new OA\Property(property: 'renewed_members_this_month', type: 'integer'),
                    new OA\Property(property: 'expired_not_renewed_members', type: 'integer'),
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
                new OA\Property(property: 'data', ref: '#/components/schemas/MemberResource')
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
        return $this->successResponse(new \Modules\MemberManager\Http\Resources\MemberResource($member), __('Member retrieved successfully'));
    }

    #[OA\Delete(
        path: '/v1/members/{id}',
        summary: '🗑️ حذف عضو (Soft Delete)',
        description: 'حذف عضو محدد من النظام ناعماً مع إخفاء كافّة اشتراكاته ودفعاته المالية وحجوزاته وسجلاته التابعة ناعماً ومتتابعاً.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم حذف العضو وسجلاته التابعة ناعماً بنجاح')]
    #[OA\Response(response: 404, description: '🚫 العضو غير موجود')]
    public function destroy($id)
    {
        $deleted = $this->memberService->deleteMember($id);
        if (!$deleted) {
            return response()->json(['message' => __('Member not found or could not be deleted')], 404);
        }
        return $this->successResponse(null, __('Member deleted successfully'));
    }

    #[OA\Post(
        path: '/v1/members/{id}/restore',
        summary: '♻️ استرجاع عضو محذوف',
        description: 'استرجاع العضو المحذوف ناعماً وكافّة اشتراكاته ودفعاته وسجلاته التابعة تلقائياً.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع العضو وكافة سجلاته التابعة بنجاح')]
    #[OA\Response(response: 404, description: '🚫 العضو غير موجود في سلة المحذوفات')]
    public function restore($id)
    {
        $restored = $this->memberService->restoreMember($id);
        if (!$restored) {
            return response()->json(['message' => __('Member not found in trashed')], 404);
        }
        return $this->successResponse(null, __('Member restored successfully'));
    }
}
