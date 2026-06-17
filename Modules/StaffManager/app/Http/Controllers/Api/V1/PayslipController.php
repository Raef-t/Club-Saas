<?php
namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\StaffManager\Services\PayslipService;
use Modules\StaffManager\Http\Requests\StorePayslipRequest;
use Modules\StaffManager\Http\Requests\UpdatePayslipRequest;
use Modules\StaffManager\Http\Resources\PayslipResource;
use OpenApi\Attributes as OA;

class PayslipController extends BaseController
{
    protected $service;

    public function __construct(PayslipService $service) {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/payslips',
        summary: '📄 عرض إيصالات الدفع',
        description: 'استرجاع جميع إيصالات الدفع (Payslips) الصادرة للموظفين.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع إيصالات الدفع بنجاح',
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
        return $this->successResponse(PayslipResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/payslips',
        summary: '➕ إضافة إيصال دفع',
        description: 'إنشاء إيصال دفع جديد يدوياً.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['payroll_run_id', 'staff_id', 'basic_salary', 'net_salary'],
            properties: [
                new OA\Property(property: 'payroll_run_id', type: 'integer', example: 1),
                new OA\Property(property: 'staff_id', type: 'integer', example: 1),
                new OA\Property(property: 'basic_salary', type: 'number', format: 'float', example: 5000.00),
                new OA\Property(property: 'net_salary', type: 'number', format: 'float', example: 4500.00)
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
    public function store(StorePayslipRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new PayslipResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/payslips/{id}',
        summary: '🔍 تفاصيل إيصال الدفع',
        description: 'استرجاع تفاصيل إيصال الدفع مع الراتب والخصومات والمكافآت.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الإيصال', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل الإيصال',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الإيصال', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id) {
        return $this->successResponse(new PayslipResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/payslips/{id}',
        summary: '📝 تعديل إيصال دفع',
        description: 'تحديث بيانات إيصال دفع موجود.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الإيصال', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'net_salary', type: 'number', format: 'float', example: 4800.00)
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
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الإيصال', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdatePayslipRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new PayslipResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/payslips/{id}',
        summary: '🗑️ حذف إيصال دفع',
        description: 'حذف إيصال دفع من النظام.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الإيصال', schema: new OA\Schema(type: 'integer', example: 1))]
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
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الإيصال', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
