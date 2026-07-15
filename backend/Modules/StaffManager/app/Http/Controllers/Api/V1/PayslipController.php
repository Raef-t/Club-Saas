<?php
namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\StaffManager\Models\Payslip;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Http\Requests\UpdatePayslipRequest;
use Modules\StaffManager\Http\Resources\PayslipResource;
use OpenApi\Attributes as OA;

class PayslipController extends BaseController
{
    #[OA\Get(
        path: '/v1/payslips',
        summary: '📄 عرض سجلات الرواتب',
        description: 'استرجاع جميع سجلات الرواتب لكل الموظفين مع تفاصيل الراتب الأساسي والعمولات والمكافآت والخصومات.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع السجلات بنجاح',
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
        $payslips = Payslip::with(['staff.person', 'staff.coachDetail', 'payrollRun'])->get();
        return $this->successResponse(PayslipResource::collection($payslips), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/payslips/{payslip}',
        summary: '📝 تعديل سجل راتب (إضافة خصم أو مكافأة)',
        description: 'تحديث بيانات الراتب لشهر معين مثل الراتب الأساسي، العمولات، المكافآت مع سببها، والخصومات مع سببها.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'payslip', in: 'path', required: true, description: 'معرف سجل الراتب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'base_pay', type: 'number', format: 'float', example: 4000.00),
                new OA\Property(property: 'commission_pay', type: 'number', format: 'float', example: 500.00),
                new OA\Property(property: 'deductions', type: 'number', format: 'float', example: 100.00),
                new OA\Property(property: 'deduction_reason', type: 'string', example: 'غياب يوم'),
                new OA\Property(property: 'bonuses', type: 'number', format: 'float', example: 200.00),
                new OA\Property(property: 'bonus_reason', type: 'string', example: 'أداء ممتاز')
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
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على السجل', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function update(UpdatePayslipRequest $request, $id) {
        $payslip = Payslip::findOrFail($id);
        
        $data = $request->validated();
        $payslip->fill($data);

        // Recalculate net_pay
        $payslip->net_pay = $payslip->base_pay + $payslip->commission_pay + $payslip->bonuses - $payslip->deductions;
        $payslip->save();

        // Reload relationships for the resource
        $payslip->load(['staff.person', 'staff.coachDetail']);
        
        return $this->successResponse(new PayslipResource($payslip), 'Updated successfully');
    }

    // Removed updateSettings
}
