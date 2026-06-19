<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\MemberManager\Services\MemberMeasurementService;
use Modules\MemberManager\Http\Requests\StoreMemberMeasurementRequest;
use Modules\MemberManager\Http\Requests\UpdateMemberMeasurementRequest;
use Modules\MemberManager\Http\Resources\MemberMeasurementResource;
use OpenApi\Attributes as OA;

class MemberMeasurementController extends BaseController
{
    protected $service;

    public function __construct(MemberMeasurementService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/measurements',
        summary: '📏 عرض جميع القياسات',
        description: 'استرجاع جميع سجلات القياسات الحيوية للأعضاء المدخلة في النظام.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ قائمة القياسات',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'member_id', type: 'integer', example: 10),
                    new OA\Property(property: 'weight', type: 'number', format: 'float', example: 75.5),
                    new OA\Property(property: 'height', type: 'number', format: 'float', example: 180.0)
                ]))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index()
    {
        return $this->successResponse(MemberMeasurementResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/measurements',
        summary: '➕ إضافة قياس جديد',
        description: 'إضافة سجل قياسات حيوية جديد لعضو.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id', 'weight', 'measurement_date'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 10),
                new OA\Property(property: 'weight', type: 'number', format: 'float', example: 75.5),
                new OA\Property(property: 'height', type: 'number', format: 'float', example: 180.0),
                new OA\Property(property: 'measurement_date', type: 'string', format: 'date', example: '2023-10-15')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء القياس بنجاح',
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
    public function store(StoreMemberMeasurementRequest $request)
    {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new MemberMeasurementResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/measurements/{measurement}',
        summary: '🔍 تفاصيل القياس',
        description: 'استرجاع تفاصيل سجل قياس محدد عن طريق المعرف.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'measurement', in: 'path', required: true, description: 'معرف القياس', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل القياس',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على القياس', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        return $this->successResponse(new MemberMeasurementResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/measurements/{measurement}',
        summary: '📝 تعديل القياس',
        description: 'تعديل تفاصيل سجل قياس موجود.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'measurement', in: 'path', required: true, description: 'معرف القياس', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'weight', type: 'number', format: 'float', example: 72.5)
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
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على القياس', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateMemberMeasurementRequest $request, $id)
    {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new MemberMeasurementResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/measurements/{measurement}',
        summary: '🗑️ حذف القياس',
        description: 'حذف سجل قياس من النظام.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'measurement', in: 'path', required: true, description: 'معرف القياس', schema: new OA\Schema(type: 'integer', example: 1))]
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
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على القياس', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id)
    {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
