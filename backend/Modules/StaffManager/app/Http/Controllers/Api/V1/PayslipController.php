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
        description: 'استرجاع جميع سجلات وقسائم الرواتب للموظفين والمدربين مع تفاصيل الراتب الأساسي والعمولات والمكافآت والخصومات وحالة الصرف.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'تصفية حسب حالة الصرف (pending, paid)', schema: new OA\Schema(type: 'string', example: 'pending'))]
    #[OA\Parameter(name: 'staff_id', in: 'query', required: false, description: 'تصفية حسب معرف الموظف/المدرب', schema: new OA\Schema(type: 'integer', example: 5))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع سجلات الرواتب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'payroll_run_id', type: 'integer', nullable: true, example: 2),
                            new OA\Property(property: 'staff_id', type: 'integer', example: 5),
                            new OA\Property(property: 'base_pay', type: 'number', format: 'float', example: 5000.00),
                            new OA\Property(property: 'commission_pay', type: 'number', format: 'float', example: 750.00),
                            new OA\Property(property: 'subscribers_count', type: 'integer', nullable: true, example: 15),
                            new OA\Property(property: 'net_pay', type: 'number', format: 'float', example: 5650.00),
                            new OA\Property(property: 'status', type: 'string', example: 'pending'),
                            new OA\Property(
                                property: 'staff',
                                type: 'object',
                                nullable: true,
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 5),
                                    new OA\Property(property: 'job_title', type: 'string', example: 'مدرب سباحة'),
                                    new OA\Property(
                                        property: 'person',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'full_name', type: 'string', example: 'أحمد علي'),
                                            new OA\Property(property: 'phone', type: 'string', example: '0501234567')
                                        ]
                                    )
                                ]
                            ),
                            new OA\Property(
                                property: 'adjustments',
                                type: 'array',
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'type', type: 'string', example: 'bonus'),
                                        new OA\Property(property: 'amount', type: 'number', example: 100.00),
                                        new OA\Property(property: 'reason', type: 'string', example: 'مكافأة أداء')
                                    ]
                                )
                            ),
                            new OA\Property(property: 'created_at', type: 'string', example: '2026-07-31T12:00:00.000000Z'),
                            new OA\Property(property: 'updated_at', type: 'string', example: '2026-07-31T12:00:00.000000Z')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request) {
        $query = Payslip::with(['staff.person', 'staff.coachDetail', 'staff.branches', 'payrollRun', 'adjustments', 'salaryPayments']);

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->input('staff_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('branch_id')) {
            $branchId = $request->input('branch_id');
            $query->whereHas('staff.branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            });
        }

        if ($request->has('per_page') && $request->input('per_page') !== 'all') {
            $perPage = min(max((int) $request->input('per_page'), 1), 100);
            $payslips = $query->latest('id')->paginate($perPage);
        } else {
            $payslips = $query->latest('id')->get();
        }

        return $this->successResponse(PayslipResource::collection($payslips), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/payslips/{payslip}',
        summary: '📝 تعديل سجل راتب (إضافة خصم أو مكافأة)',
        description: 'تحديث بيانات الراتب لشهر معين بإضافة أو تعديل الحسومات والمكافآت وتحديث الصافي تلقائياً.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'payslip',
        in: 'path',
        required: true,
        description: 'معرف سجل الراتب (Payslip ID)',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'base_pay', description: 'الراتب الأساسي', type: 'number', format: 'float', example: 5000.00),
                new OA\Property(property: 'commission_pay', description: 'عمولة الأنشطة', type: 'number', format: 'float', example: 750.00),
                new OA\Property(
                    property: 'adjustments',
                    type: 'array',
                    description: 'قائمة المكافآت أو الخصومات الإضافية',
                    items: new OA\Items(
                        type: 'object',
                        required: ['type', 'amount'],
                        properties: [
                            new OA\Property(property: 'type', type: 'string', enum: ['bonus', 'deduction'], example: 'bonus'),
                            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150.00),
                            new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'مكافأة تميز في العمل')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث سجل الراتب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'staff_id', type: 'integer', example: 5),
                        new OA\Property(property: 'base_pay', type: 'number', example: 5000.00),
                        new OA\Property(property: 'commission_pay', type: 'number', example: 750.00),
                        new OA\Property(property: 'net_pay', type: 'number', example: 5900.00),
                        new OA\Property(property: 'status', type: 'string', example: 'pending'),
                        new OA\Property(
                            property: 'adjustments',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'type', type: 'string', example: 'bonus'),
                                    new OA\Property(property: 'amount', type: 'number', example: 150.00),
                                    new OA\Property(property: 'reason', type: 'string', example: 'مكافأة تميز في العمل')
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ لا يمكن تعديل قسيمة راتب تم صرفها محاسبياً',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'لا يمكن تعديل قسيمة راتب تم صرفها بالفعل في النظام المحاسبي. يرجى إلغاء سند الصرف أولاً.')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 سجل الراتب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdatePayslipRequest $request, $id) {
        $payslip = Payslip::findOrFail($id);

        if ($payslip->status === 'paid') {
            return $this->errorResponse('لا يمكن تعديل قسيمة راتب تم صرفها بالفعل في النظام المحاسبي. يرجى إلغاء سند الصرف أولاً.', 422);
        }

        $data = $request->validated();

        \Illuminate\Support\Facades\DB::transaction(function () use ($payslip, $data) {
            if (isset($data['base_pay'])) {
                $payslip->base_pay = $data['base_pay'];
            }
            if (isset($data['commission_pay'])) {
                $payslip->commission_pay = $data['commission_pay'];
            }

            // Synchronize adjustments if key is present
            if (array_key_exists('adjustments', $data)) {
                $payslip->adjustments()->delete();
                if (!empty($data['adjustments'])) {
                    foreach ($data['adjustments'] as $adj) {
                        $payslip->adjustments()->create([
                            'type'   => $adj['type'],
                            'amount' => $adj['amount'],
                            'reason' => $adj['reason'] ?? null,
                        ]);
                    }
                }
            }

            // Recalculate net_pay dynamically from adjustments relation
            $payslip->load('adjustments');
            $totalDeductions = $payslip->adjustments->where('type', 'deduction')->sum('amount');
            $totalBonuses    = $payslip->adjustments->where('type', 'bonus')->sum('amount');
            
            $payslip->net_pay = max(0, (float) $payslip->base_pay + (float) $payslip->commission_pay + $totalBonuses - $totalDeductions);
            $payslip->save();
        });

        $payslip->load(['staff.person', 'staff.coachDetail', 'adjustments']);
        return $this->successResponse(new PayslipResource($payslip), 'Updated successfully');
    }

    #[OA\Post(
        path: '/v1/payslips/generate',
        summary: '⚙️ توليد مسودة الرواتب',
        description: 'احتساب وتوليد الرواتب كمسودة ترجع للواجهة الأمامية للمعاينة دون حفظها في قاعدة البيانات.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['branch_id'],
            properties: [
                new OA\Property(property: 'branch_id', type: 'integer', example: 1, description: 'معرف الفرع الإلزامي لحساب الرواتب')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم حساب مسودة الرواتب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم حساب مسودة الرواتب بنجاح'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'period_start', type: 'string', example: '2026-07-01'),
                        new OA\Property(property: 'period_end', type: 'string', example: '2026-07-31'),
                        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                        new OA\Property(property: 'total_net_pay', type: 'number', example: 25000.00),
                        new OA\Property(property: 'total_commission_pay', type: 'number', example: 3500.00),
                        new OA\Property(
                            property: 'payslips',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'staff_id', type: 'integer', example: 5),
                                    new OA\Property(property: 'staff_name', type: 'string', example: 'أحمد علي'),
                                    new OA\Property(property: 'base_pay', type: 'number', example: 5000.00),
                                    new OA\Property(property: 'commission_pay', type: 'number', example: 750.00),
                                    new OA\Property(property: 'subscribers_count', type: 'integer', example: 15),
                                    new OA\Property(property: 'net_pay', type: 'number', example: 5750.00),
                                    new OA\Property(property: 'adjustments', type: 'array', items: new OA\Items(type: 'object'))
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ معرف الفرع مطلوب', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'معرف الفرع (branch_id) مطلوب.')]))]
    #[OA\Response(response: 400, description: '❌ إعدادات غير مكتملة أو خطأ في الحسابات', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'خطأ في عملية حساب المسودة.')]))]
    #[OA\Response(response: 409, description: '❌ مسير الرواتب موجود ومثبت مسبقاً', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'مسير الرواتب مثبت مسبقاً لهذه الفترة.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function generate(Request $request) {
        $branchId = $request->input('branch_id');
        if (!$branchId) {
            return $this->errorResponse('معرف الفرع (branch_id) مطلوب.', 422);
        }

        try {
            $previewData = $this->payrollService->generatePreview($branchId);
            return $this->successResponse($previewData, 'تم حساب مسودة الرواتب بنجاح');
        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 500) ? $e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    #[OA\Post(
        path: '/v1/payslips/confirm',
        summary: '🔒 تثبيت وحفظ الرواتب',
        description: 'استقبال الرواتب النهائية بعد التعديل وحفظها مع الحسومات والزيادات في قاعدة البيانات بشكل رسمي.',
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
                            new OA\Property(property: 'staff_id', type: 'integer', example: 5),
                            new OA\Property(property: 'staff_name', type: 'string', nullable: true, example: 'أحمد علي'),
                            new OA\Property(property: 'base_pay', type: 'number', example: 5000.00),
                            new OA\Property(property: 'commission_pay', type: 'number', example: 750.00),
                            new OA\Property(property: 'subscribers_count', type: 'integer', nullable: true, example: 15),
                            new OA\Property(property: 'net_pay', type: 'number', example: 5650.00),
                            new OA\Property(
                                property: 'adjustments',
                                type: 'array',
                                nullable: true,
                                items: new OA\Items(
                                    type: 'object',
                                    required: ['type', 'amount'],
                                    properties: [
                                        new OA\Property(property: 'type', type: 'string', enum: ['bonus', 'deduction'], example: 'bonus'),
                                        new OA\Property(property: 'amount', type: 'number', example: 100.00),
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
    #[OA\Response(
        response: 200,
        description: '✅ تم تثبيت الرواتب وحفظها بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم تثبيت الرواتب وحفظها بنجاح'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(
        response: 409,
        description: '❌ الرواتب مثبتة مسبقاً لهذا الشهر',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'تم تثبيت الرواتب مسبقاً لهذا الشهر.')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function confirm(Request $request) {
        $data = $request->validate([
            'branch_id' => 'required|integer',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'payslips' => 'required|array',
            'payslips.*.staff_id' => 'required|integer',
            'payslips.*.staff_name' => 'nullable|string',
            'payslips.*.base_pay' => 'required|numeric',
            'payslips.*.commission_pay' => 'required|numeric',
            'payslips.*.subscribers_count' => 'nullable|integer|min:0',
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
