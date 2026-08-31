<?php
namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\Sports\Services\StaffCommissionRuleService;
use Modules\Sports\Http\Requests\StoreStaffCommissionRuleRequest;
use Modules\Sports\Http\Requests\UpdateStaffCommissionRuleRequest;
use Modules\Sports\Http\Resources\StaffCommissionRuleResource;
use OpenApi\Attributes as OA;

class StaffCommissionRuleController extends BaseController
{
    protected $service;

    public function __construct(StaffCommissionRuleService $service) {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/staff-commission-rules',
        summary: '💰 عرض قواعد عمولات الموظفين',
        description: 'استرجاع جميع القواعد الخاصة بعمولات الموظفين والمدربين.',
        tags: ['Staff Commission Rules'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم الاسترجاع بنجاح',
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
        return $this->successResponse(StaffCommissionRuleResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/staff-commission-rules',
        summary: '➕ إضافة قاعدة عمولة',
        description: 'إضافة قاعدة عمولة جديدة لموظف أو نشاط معين.',
        tags: ['Staff Commission Rules'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['staff_id', 'commission_type', 'commission_value'],
            properties: [
                new OA\Property(property: 'staff_id', type: 'integer', example: 1),
                new OA\Property(property: 'commission_type', type: 'string', example: 'percentage'),
                new OA\Property(property: 'commission_value', type: 'number', format: 'float', example: 10.5)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم الإنشاء بنجاح',
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
    public function store(StoreStaffCommissionRuleRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new StaffCommissionRuleResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/staff-commission-rules/{staff_commission_rule}',
        summary: '🔍 تفاصيل قاعدة العمولة',
        description: 'استرجاع تفاصيل قاعدة عمولة محددة.',
        tags: ['Staff Commission Rules'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff_commission_rule', in: 'path', required: true, description: 'المعرف', schema: new OA\Schema(type: 'integer', example: 1))]
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
        return $this->successResponse(new StaffCommissionRuleResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/staff-commission-rules/{staff_commission_rule}',
        summary: '✏️ تعديل قاعدة العمولة',
        description: 'تحديث بيانات قاعدة عمولة مسجلة.',
        tags: ['Staff Commission Rules'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff_commission_rule', in: 'path', required: true, description: 'المعرف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'commission_value', type: 'number', format: 'float', example: 15.0)
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
    public function update(UpdateStaffCommissionRuleRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new StaffCommissionRuleResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/staff-commission-rules/{staff_commission_rule}',
        summary: '🗑️ حذف قاعدة العمولة',
        description: 'حذف قاعدة العمولة من النظام.',
        tags: ['Staff Commission Rules'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff_commission_rule', in: 'path', required: true, description: 'المعرف', schema: new OA\Schema(type: 'integer', example: 1))]
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
