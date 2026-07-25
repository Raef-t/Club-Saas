<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\StaffManager\Models\Payslip;
use Modules\StaffManager\Http\Requests\UpdatePayslipRequest;
use Modules\StaffManager\Http\Resources\PayslipResource;
use Modules\StaffManager\Services\PayrollService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Exception;

class PayslipController extends BaseController
{
    protected PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

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
        $payslips = Payslip::with(['staff.person', 'staff.coachDetail', 'payrollRun', 'adjustments'])->get();
        return $this->successResponse(PayslipResource::collection($payslips), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/payslips/{payslip}',
        summary: '📝 تعديل سجل راتب (إضافة خصم أو مكافأة)',
        description: 'تحديث بيانات الراتب لشهر معين بإضافة أو تعديل الحسومات والمكافآت وتحديث الصافي.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ تم تحديث السجل بنجاح')]
    #[OA\Response(response: 404, description: '❌ السجل غير موجود')]
    public function update(UpdatePayslipRequest $request, $id) {
        $payslip = Payslip::findOrFail($id);
        $data = $request->validated();

        if (isset($data['base_pay'])) {
            $payslip->base_pay = $data['base_pay'];
        }
        if (isset($data['commission_pay'])) {
            $payslip->commission_pay = $data['commission_pay'];
        }

        // Add new adjustments if provided
        if (!empty($data['adjustments'])) {
            foreach ($data['adjustments'] as $adj) {
                $payslip->adjustments()->create([
                    'type' => $adj['type'],
                    'amount' => $adj['amount'],
                    'reason' => $adj['reason'] ?? null,
                ]);
            }
        }

        // Recalculate net_pay dynamically from adjustments relation
        $payslip->load('adjustments');
        $totalDeductions = $payslip->adjustments->where('type', 'deduction')->sum('amount');
        $totalBonuses = $payslip->adjustments->where('type', 'bonus')->sum('amount');
        
        $payslip->net_pay = $payslip->base_pay + $payslip->commission_pay + $totalBonuses - $totalDeductions;
        $payslip->save();

        $payslip->load(['staff.person', 'staff.coachDetail', 'adjustments']);
        return $this->successResponse(new PayslipResource($payslip), 'Updated successfully');
    }

    #[OA\Post(
        path: '/v1/payslips/generate',
        summary: '⚙️ توليد مسودة الرواتب',
        description: 'يقوم بحساب وتوليد الرواتب كمسودة ترجع للواجهة الأمامية بدون حفظها في قاعدة البيانات.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['branch_id'],
            properties: [
                new OA\Property(property: 'branch_id', type: 'integer', example: 1, description: 'معرف الفرع الإلزامي لتوليد الرواتب')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم حساب المسودة بنجاح')]
    #[OA\Response(response: 400, description: '❌ إعدادات غير مكتملة أو يوم عطلة')]
    #[OA\Response(response: 409, description: '❌ مسير الرواتب موجود مسبقاً')]
    public function generate(Request $request) {
        $branchId = $request->input('branch_id');
        if (!$branchId) {
            return $this->errorResponse('معرف الفرع (branch_id) مطلوب.', 422);
        }

        try {
            $previewData = $this->payrollService->generatePreview($branchId);
            return $this->successResponse($previewData, 'تم حساب مسودة الرواتب بنجاح');
        } catch (Exception $e) {
            // Determine code 400 or 409 based on message or create custom exceptions
            $code = str_contains($e->getMessage(), 'تم حساب وحفظ الرواتب مسبقاً') ? 409 : 400;
            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    #[OA\Post(
        path: '/v1/payslips/confirm',
        summary: 'تثبيت وحفظ الرواتب',
        description: 'استقبال الرواتب النهائية بعد التعديل وحفظها مع الحسومات والزيادات في قاعدة البيانات',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['branch_id', 'period_start', 'period_end', 'payslips'],
            properties: [
                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                new OA\Property(property: 'period_start', type: 'string', format: 'date', example: '2026-07-01'),
                new OA\Property(property: 'period_end', type: 'string', format: 'date', example: '2026-07-31'),
                new OA\Property(
                    property: 'payslips',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: ['staff_id', 'base_pay', 'commission_pay', 'net_pay'],
                        properties: [
                            new OA\Property(property: 'staff_id', type: 'integer', example: 1),
                            new OA\Property(property: 'base_pay', type: 'number', example: 5000),
                            new OA\Property(property: 'commission_pay', type: 'number', example: 500),
                            new OA\Property(property: 'net_pay', type: 'number', example: 5500),
                            new OA\Property(
                                property: 'adjustments',
                                type: 'array',
                                nullable: true,
                                items: new OA\Items(
                                    type: 'object',
                                    required: ['type', 'amount'],
                                    properties: [
                                        new OA\Property(property: 'type', type: 'string', enum: ['bonus', 'deduction'], example: 'bonus'),
                                        new OA\Property(property: 'amount', type: 'number', example: 100),
                                        new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'مكافأة أداء')
                                    ]
                                )
                            )
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم تثبيت الرواتب بنجاح')]
    #[OA\Response(response: 409, description: '❌ الرواتب مثبتة مسبقاً')]
    public function confirm(Request $request) {
        $data = $request->validate([
            'branch_id' => 'required|integer',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'payslips' => 'required|array',
            'payslips.*.staff_id' => 'required|integer',
            'payslips.*.base_pay' => 'required|numeric',
            'payslips.*.commission_pay' => 'required|numeric',
            'payslips.*.net_pay' => 'required|numeric',
            'payslips.*.adjustments' => 'nullable|array',
            'payslips.*.adjustments.*.type' => 'required|in:bonus,deduction',
            'payslips.*.adjustments.*.amount' => 'required|numeric|min:0',
            'payslips.*.adjustments.*.reason' => 'nullable|string',
        ]);

        try {
            $this->payrollService->confirmPayroll($data);
            return $this->successResponse(null, 'تم تثبيت الرواتب وحفظها بنجاح');
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'مسبقاً') ? 409 : 400;
            return $this->errorResponse($e->getMessage(), $code);
        }
    }
}
