<?php
namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\Sports\Services\StaffActivityService;
use Modules\Sports\Http\Requests\StoreStaffActivityRequest;
use Modules\Sports\Http\Requests\UpdateStaffActivityRequest;
use Modules\Sports\Http\Resources\StaffActivityResource;
use OpenApi\Attributes as OA;

class StaffActivityController extends BaseController
{
    protected $service;

    public function __construct(StaffActivityService $service) {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/staff-activities',
        summary: '🏋️ عرض أنشطة الموظفين',
        description: 'استرجاع جميع الأنشطة المرتبطة بالموظفين أو المدربين.',
        tags: ['Staff Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع البيانات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index() {
        return $this->successResponse(StaffActivityResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/staff-activities',
        summary: '➕ ربط موظف بنشاط',
        description: 'تعيين موظف أو مدرب لنشاط رياضي معين.',
        tags: ['Staff Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['staff_id', 'activity_id'],
            properties: [
                new OA\Property(property: 'staff_id', type: 'integer', example: 1),
                new OA\Property(property: 'activity_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم الربط بنجاح',
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
    public function store(StoreStaffActivityRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new StaffActivityResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/staff-activities/{staff_activity}',
        summary: '🔍 تفاصيل الربط بين الموظف والنشاط',
        description: 'استرجاع تفاصيل العلاقة بين الموظف والنشاط الرياضي.',
        tags: ['Staff Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff_activity', in: 'path', required: true, description: 'المعرف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ التفاصيل',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id) {
        return $this->successResponse(new StaffActivityResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/staff-activities/{staff_activity}',
        summary: '✏️ تعديل ربط الموظف بالنشاط',
        description: 'تحديث البيانات المتعلقة بنشاط الموظف.',
        tags: ['Staff Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff_activity', in: 'path', required: true, description: 'المعرف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'activity_id', type: 'integer', example: 2)
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
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateStaffActivityRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new StaffActivityResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/staff-activities/{staff_activity}',
        summary: '🗑️ حذف العلاقة',
        description: 'إزالة العلاقة بين الموظف والنشاط الرياضي.',
        tags: ['Staff Activities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff_activity', in: 'path', required: true, description: 'المعرف', schema: new OA\Schema(type: 'integer', example: 1))]
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
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
