<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\MemberManager\Services\MemberHealthProfileService;
use Modules\MemberManager\Http\Requests\StoreMemberHealthProfileRequest;
use Modules\MemberManager\Http\Requests\UpdateMemberHealthProfileRequest;
use Modules\MemberManager\Http\Resources\MemberHealthProfileResource;
use OpenApi\Attributes as OA;

class MemberHealthProfileController extends BaseController
{
    protected $service;

    public function __construct(MemberHealthProfileService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/health-profiles',
        summary: '🏥 عرض جميع الملفات الصحية',
        description: 'استرجاع جميع الملفات الصحية للأعضاء المدخلة في النظام.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ قائمة الملفات الصحية',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'member_id', type: 'integer', example: 10),
                    new OA\Property(property: 'blood_type', type: 'string', example: 'O+'),
                    new OA\Property(property: 'medical_conditions', type: 'array', items: new OA\Items(type: 'string', example: 'السكري'))
                ]))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index()
    {
        return $this->successResponse(MemberHealthProfileResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/health-profiles',
        summary: '➕ إضافة ملف صحي جديد',
        description: 'إنشاء ملف صحي جديد لعضو.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id', 'blood_type'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 10),
                new OA\Property(property: 'blood_type', type: 'string', example: 'A+'),
                new OA\Property(property: 'medical_conditions', type: 'array', items: new OA\Items(type: 'string', example: 'الربو'))
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الملف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreMemberHealthProfileRequest $request)
    {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new MemberHealthProfileResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/health-profiles/{health_profile}',
        summary: '🔍 تفاصيل الملف الصحي',
        description: 'استرجاع تفاصيل سجل صحي محدد عن طريق المعرف.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'health_profile', in: 'path', required: true, description: 'معرف الملف الصحي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل الملف الصحي',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الملف', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        return $this->successResponse(new MemberHealthProfileResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/health-profiles/{health_profile}',
        summary: '📝 تعديل الملف الصحي',
        description: 'تعديل تفاصيل سجل صحي موجود.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'health_profile', in: 'path', required: true, description: 'معرف الملف الصحي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'blood_type', type: 'string', example: 'B+')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التعديل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الملف', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateMemberHealthProfileRequest $request, $id)
    {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new MemberHealthProfileResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/health-profiles/{health_profile}',
        summary: '🗑️ حذف الملف الصحي',
        description: 'حذف سجل صحي من النظام.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'health_profile', in: 'path', required: true, description: 'معرف الملف الصحي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الملف', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id)
    {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
