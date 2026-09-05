<?php
namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\StaffManager\Services\StaffShiftService;
use Modules\StaffManager\Http\Requests\StoreStaffShiftRequest;
use Modules\StaffManager\Http\Requests\UpdateStaffShiftRequest;
use Modules\StaffManager\Http\Resources\StaffShiftResource;
use OpenApi\Attributes as OA;

class StaffShiftController extends BaseController
{
    protected $service;

    public function __construct(StaffShiftService $service) {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/staff-shifts',
        summary: '🕒 عرض مناوبات الموظفين',
        description: 'استرجاع قائمة بجميع مناوبات الموظفين في الفروع.',
        tags: ['Staff Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع المناوبات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request) {
        return $this->successResponse(StaffShiftResource::collection($this->service->getAll($request->all())), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/staff-shifts',
        summary: '➕ إضافة مناوبة لموظف',
        description: 'تسجيل مناوبة عمل جديدة لموظف معين.',
        tags: ['Staff Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['staff_id', 'branch_shift_id', 'date'],
            properties: [
                new OA\Property(property: 'staff_id', type: 'integer', example: 1),
                new OA\Property(property: 'branch_shift_id', type: 'integer', example: 1),
                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2023-11-01')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء المناوبة بنجاح',
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
    public function store(StoreStaffShiftRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new StaffShiftResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/staff-shifts/{staff_shift}',
        summary: '🔍 تفاصيل مناوبة الموظف',
        description: 'استرجاع تفاصيل مناوبة محددة لموظف.',
        tags: ['Staff Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff_shift', in: 'path', required: true, description: 'معرف المناوبة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل المناوبة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على المناوبة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id) {
        return $this->successResponse(new StaffShiftResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/staff-shifts/{staff_shift}',
        summary: '📝 تعديل مناوبة الموظف',
        description: 'تحديث بيانات مناوبة عمل مخصصة لموظف.',
        tags: ['Staff Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff_shift', in: 'path', required: true, description: 'معرف المناوبة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2023-11-02')
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
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على المناوبة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateStaffShiftRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new StaffShiftResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/staff-shifts/{staff_shift}',
        summary: '🗑️ حذف مناوبة',
        description: 'حذف مناوبة عمل مسجلة لموظف.',
        tags: ['Staff Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff_shift', in: 'path', required: true, description: 'معرف المناوبة', schema: new OA\Schema(type: 'integer', example: 1))]
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
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على المناوبة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
