<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\MemberManager\Http\Requests\StoreMemberRequest;
use Modules\MemberManager\Http\Requests\RecordMeasurementRequest;
use Modules\MemberManager\Http\Resources\MemberResource;
use Modules\MemberManager\Services\MemberService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class MemberController extends BaseController
{
    protected $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    #[OA\Get(
        path: '/v1/members',
        summary: '👥 عرض جميع الأعضاء',
        description: 'استرجاع قائمة بجميع الأعضاء مع دعم الفلترة (مثل: حالة العضوية، نوع المشترك).',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ قائمة الأعضاء',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Members retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(type: 'object', properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'member_number', type: 'string', example: 'MEM-0001'),
                        new OA\Property(property: 'full_name', type: 'string', example: 'محمد أحمد'),
                        new OA\Property(property: 'membership_status', type: 'string', example: 'active')
                    ])
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    #[OA\Response(response: 500, description: '🔥 خطأ في الخادم', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'حدث خطأ داخلي.')]))]
    public function index(\Illuminate\Http\Request $request)
    {
        $members = $this->memberService->getAllMembers($request->all());
        return $this->successResponse(
            MemberResource::collection($members)->response()->getData(true),
            __('Members retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/members',
        summary: '➕ تسجيل عضو جديد',
        description: 'تسجيل عضو جديد مع معلوماته الشخصية وبيانات التواصل.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['person_id'],
            properties: [
                new OA\Property(property: 'person_id', type: 'integer', description: 'معرف الشخص المرتبط بالعضو', example: 50)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم تسجيل العضو بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member registered successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 10),
                    new OA\Property(property: 'member_number', type: 'string', example: 'MEM-0010')
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من صحة البيانات',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'),
                new OA\Property(property: 'errors', type: 'object', properties: [
                    new OA\Property(property: 'person_id', type: 'array', items: new OA\Items(type: 'string', example: 'حقل معرف الشخص مطلوب.'))
                ])
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreMemberRequest $request)
    {
        $member = $this->memberService->registerMember($request->validated());
        return $this->successResponse(new MemberResource($member), __('Member registered successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/members/{id}',
        summary: '🔍 تفاصيل العضو',
        description: 'استرجاع جميع تفاصيل عضو محدد عن طريق المعرف (ID).',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع العضو بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member retrieved successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'member_number', type: 'string', example: 'MEM-0001'),
                    new OA\Property(property: 'membership_status', type: 'string', example: 'active')
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 لم يتم العثور على العضو',
        content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member not found.')])
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $member = $this->memberService->getMemberById($id);
        return $this->successResponse(new MemberResource($member), __('Member retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/members/{id}',
        summary: '📝 تحديث بيانات العضو',
        description: 'تعديل البيانات الخاصة بعضو موجود في النظام.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'membership_status', type: 'string', enum: ['active', 'inactive', 'frozen'], example: 'frozen')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التحديث بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من صحة البيانات',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'),
                new OA\Property(property: 'errors', type: 'object', properties: [
                    new OA\Property(property: 'membership_status', type: 'array', items: new OA\Items(type: 'string', example: 'حالة العضوية غير صالحة.'))
                ])
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على العضو', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(StoreMemberRequest $request, $id)
    {
        $member = $this->memberService->updateMember($id, $request->validated());
        return $this->successResponse(new MemberResource($member), __('Member updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/members/{id}',
        summary: '🗑️ حذف عضو',
        description: 'حذف العضو نهائياً من النظام.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على العضو', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id)
    {
        $this->memberService->deleteMember($id);
        return $this->successResponse(null, __('Member deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/members/{id}/health-profile',
        summary: '🏥 الملف الصحي للعضو',
        description: 'استرجاع الملف الصحي للعضو (الأمراض، الإصابات، إلخ).',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الملف الصحي بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Health profile retrieved'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'blood_type', type: 'string', example: 'A+'),
                    new OA\Property(property: 'medical_conditions', type: 'array', items: new OA\Items(type: 'string', example: 'الربو'))
                ])
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على العضو', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function getHealthProfile($id)
    {
        $profile = $this->memberService->getHealthProfile($id);
        return $this->successResponse($profile, __('Health profile retrieved'));
    }

    #[OA\Get(
        path: '/v1/members/{id}/measurements',
        summary: '📏 قياسات العضو',
        description: 'استرجاع سجل جميع القياسات الحيوية (الوزن، الطول، نسبة الدهون) المرتبطة بالعضو.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع القياسات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Measurements retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'weight', type: 'number', format: 'float', example: 75.5),
                    new OA\Property(property: 'height', type: 'number', format: 'float', example: 178.0),
                    new OA\Property(property: 'bmi', type: 'number', format: 'float', example: 23.8),
                    new OA\Property(property: 'measurement_date', type: 'string', format: 'date', example: '2023-11-01')
                ]))
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على العضو', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function getMeasurements($id)
    {
        $measurements = $this->memberService->getMeasurements($id);
        return $this->successResponse($measurements, __('Measurements retrieved'));
    }

    #[OA\Post(
        path: '/v1/members/{id}/measurements',
        summary: '⚖️ إضافة قياس جديد',
        description: 'إضافة سجل قياسات حيوية جديد للعضو المختار.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['weight', 'height', 'measurement_date'],
            properties: [
                new OA\Property(property: 'weight', type: 'number', format: 'float', description: 'الوزن بالكيلوجرام', example: 75.5),
                new OA\Property(property: 'height', type: 'number', format: 'float', description: 'الطول بالسنتيمتر', example: 180.0),
                new OA\Property(property: 'body_fat_percentage', type: 'number', format: 'float', description: 'نسبة الدهون', example: 15.2),
                new OA\Property(property: 'measurement_date', type: 'string', format: 'date', description: 'تاريخ القياس', example: '2023-10-15')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم تسجيل القياس بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Measurement recorded successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 10),
                    new OA\Property(property: 'weight', type: 'number', format: 'float', example: 75.5)
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من صحة البيانات',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'),
                new OA\Property(property: 'errors', type: 'object', properties: [
                    new OA\Property(property: 'weight', type: 'array', items: new OA\Items(type: 'string', example: 'حقل الوزن مطلوب.'))
                ])
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على العضو', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function recordMeasurement(RecordMeasurementRequest $request, $id)
    {
        $measurement = $this->memberService->recordMeasurement($id, $request->validated());
        return $this->successResponse($measurement, __('Measurement recorded successfully'), 201);
    }
}
