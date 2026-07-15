<?php
namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\StaffManager\Models\Payslip;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\PayrollRun;
use Modules\StaffManager\Models\StaffIncomeEntry;
use Modules\StaffManager\Http\Requests\UpdatePayslipRequest;
use Modules\StaffManager\Http\Resources\PayslipResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    #[OA\Post(
        path: '/v1/payslips/generate',
        summary: '⚙️ توليد رواتب الموظفين',
        description: 'يقوم بحساب وتوليد سجلات الرواتب لجميع الموظفين النشطين لفترة محددة (شهر). إذا لم يتم تحديد الشهر يُستخدم الشهر الحالي تلقائياً.',
        tags: ['Payslips'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'period', type: 'string', example: '2026-07', description: 'الشهر بصيغة YYYY-MM، اتركه فارغاً للشهر الحالي')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم توليد الرواتب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Payroll generated successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'period', type: 'string', example: '2026-07'),
                    new OA\Property(property: 'payroll_run_id', type: 'integer', example: 1),
                    new OA\Property(property: 'staff_count', type: 'integer', example: 12)
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 409,
        description: '⚠️ الرواتب لهذه الفترة تم توليدها مسبقاً',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'Payroll for this period already exists.')
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function generate(Request $request) {
        $period = $request->input('period', now()->format('Y-m'));

        // Validate period format
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            return $this->errorResponse('Invalid period format. Use YYYY-MM.', 422);
        }

        $periodStart = \Carbon\Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $periodEnd   = $periodStart->copy()->endOfMonth();

        // Check if already generated for this period
        $existing = PayrollRun::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        if ($existing) {
            return $this->errorResponse(
                "Payroll for period {$period} already exists (Run ID: {$existing->id}).",
                409
            );
        }

        $staffCount = 0;

        DB::transaction(function () use ($periodStart, $periodEnd, &$staffCount) {
            $payrollRun = PayrollRun::create([
                'period_start' => $periodStart->toDateString(),
                'period_end'   => $periodEnd->toDateString(),
                'status'       => 'draft',
            ]);

            $staffMembers = Staff::where('is_active', true)->get();

            foreach ($staffMembers as $staff) {
                $basePay       = 0;
                $commissionPay = 0;

                if (in_array($staff->employment_type, ['fixed_salary', 'hybrid'])) {
                    $basePay = $staff->base_salary ?? 0;
                }

                if (in_array($staff->employment_type, ['hybrid', 'percentage_only', 'commission'])) {
                    $commissionPay = StaffIncomeEntry::where('staff_id', $staff->id)
                        ->where('status', 'pending')
                        ->where('type', 'commission')
                        ->where('created_at', '<=', now())
                        ->sum('amount');
                }

                if ($basePay > 0 || $commissionPay > 0) {
                    $netPay = $basePay + $commissionPay;
                    $payrollRun->payslips()->create([
                        'staff_id'       => $staff->id,
                        'base_pay'       => $basePay,
                        'commission_pay' => $commissionPay,
                        'deductions'     => 0,
                        'bonuses'        => 0,
                        'net_pay'        => $netPay,
                    ]);
                    $staffCount++;
                }
            }

            // Store payroll run ID for the response
            $this->_lastPayrollRun = $payrollRun;
        });

        return $this->successResponse([
            'period'         => $period,
            'payroll_run_id' => $this->_lastPayrollRun->id,
            'staff_count'    => $staffCount,
        ], 'Payroll generated successfully');
    }

    private ?PayrollRun $_lastPayrollRun = null;
}
