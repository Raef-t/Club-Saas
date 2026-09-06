<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\StaffManager\Services\PayrollService;
use Modules\StaffManager\Http\Resources\PayrollRunResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PayrollController extends BaseController
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    #[OA\Get(
        path: '/v1/payroll-runs',
        summary: '💰 عرض مسيرات الرواتب',
        description: 'استرجاع قائمة مسيرات الرواتب المنفذة في النظام مع إمكانية التصفية حسب الفرع والترقيم الصفحات.',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية مسيرات الرواتب حسب معرف الفرع',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'عدد العناصر في الصفحة (أو اكتب "all" لجلب كل السجلات بدون ترقيم صفحات)',
        schema: new OA\Schema(type: 'string', example: '15')
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'رقم الصفحة المستهدفة',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع قائمة مسيرات الرواتب بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Payroll runs retrieved',
                'data' => [
                    [
                        'id' => 1,
                        'branch_id' => 1,
                        'period_start' => '2026-01-01',
                        'period_end' => '2026-01-31',
                        'status' => 'draft',
                        'payslips_count' => 12,
                        'created_at' => '2026-01-31T18:00:00.000000Z'
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح - رمز المرور مفقود أو غير صالحة',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function index(\Illuminate\Http\Request $request)
    {
        $runs = $this->payrollService->getAllPayrollRuns($request->all());
        return $this->successResponse(PayrollRunResource::collection($runs), __('Payroll runs retrieved'));
    }

    #[OA\Post(
        path: '/v1/payroll-runs',
        summary: '➕ إنشاء مسير رواتب جديد',
        description: 'بدء تشغيل وإنشاء مسير رواتب جديد لفترة زمنية محددة.',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'الفترة الزمنية لمسير الرواتب المراد إنشاؤه',
        content: new OA\JsonContent(
            required: ['period_start', 'period_end'],
            properties: [
                new OA\Property(property: 'period_start', type: 'string', format: 'date', example: '2026-01-01', description: 'تاريخ بداية فترة مسير الرواتب'),
                new OA\Property(property: 'period_end', type: 'string', format: 'date', example: '2026-01-31', description: 'تاريخ نهاية فترة مسير الرواتب (يجب أن يكون بعد تاريخ البداية)')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء مسير الرواتب بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Payroll run created',
                'data' => [
                    'id' => 1,
                    'branch_id' => 1,
                    'period_start' => '2026-01-01',
                    'period_end' => '2026-01-31',
                    'status' => 'draft',
                    'created_at' => '2026-01-31T18:00:00.000000Z'
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من صحة التواريخ المدخلة',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => [
                    'period_end' => ['The period end must be a date after period start.']
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function store(Request $request)
    {
        $data = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);

        $run = $this->payrollService->createPayrollRun($data['period_start'], $data['period_end']);
        return $this->successResponse(new PayrollRunResource($run), __('Payroll run created'), 201);
    }

    #[OA\Get(
        path: '/v1/payroll-runs/{payroll_run}',
        summary: '🔍 تفاصيل مسير الرواتب',
        description: 'استرجاع تفاصيل مسير رواتب محدد مع مصفوفة إيصالات الدفع والرواتب (Payslips) المرتبطة به.',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'payroll_run',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لمسير الرواتب',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تفاصيل مسير الرواتب بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Payroll run retrieved',
                'data' => [
                    'id' => 1,
                    'branch_id' => 1,
                    'period_start' => '2026-01-01',
                    'period_end' => '2026-01-31',
                    'status' => 'draft',
                    'created_at' => '2026-01-31T18:00:00.000000Z',
                    'payslips' => [
                        [
                            'id' => 101,
                            'payroll_run_id' => 1,
                            'staff_id' => 5,
                            'base_salary' => 5000.00,
                            'allowances' => 500.00,
                            'deductions' => 100.00,
                            'net_salary' => 5400.00,
                            'status' => 'draft'
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 مسير الرواتب غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Record not found.'
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function show(int $id)
    {
        $run = $this->payrollService->getPayrollRunById($id);
        return $this->successResponse(new PayrollRunResource($run), __('Payroll run retrieved'));
    }

    #[OA\Post(
        path: '/v1/payroll-runs/{id}/generate-payslips',
        summary: '⚙️ توليد إيصالات الدفع والرواتب',
        description: 'إنشاء إيصالات الدفع الفردية (Payslips) لجميع الموظفين المستحقين ضمن مسير رواتب محدد.',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لمسير الرواتب المراد توليد قسائم رواتبه',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم توليد إيصالات الدفع بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Payslips generated successfully',
                'data' => [
                    'id' => 1,
                    'branch_id' => 1,
                    'period_start' => '2026-01-01',
                    'period_end' => '2026-01-31',
                    'status' => 'draft',
                    'payslips_count' => 15,
                    'created_at' => '2026-01-31T18:00:00.000000Z'
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 مسير الرواتب غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Record not found.'
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function generatePayslips(int $id)
    {
        $run = $this->payrollService->generatePayslips($id);
        return $this->successResponse(new PayrollRunResource($run), __('Payslips generated successfully'));
    }

    #[OA\Delete(
        path: '/v1/payroll-runs/{payroll_run}',
        summary: '🗑️ التراجع عن مسير الرواتب وحذفه',
        description: 'إلغاء مسير الرواتب المثبت وحذف كافة إيصالات الرواتب التابعة له للعودة إلى حالة المسودة.',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'payroll_run',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لمسير الرواتب المراد التراجع عنه وحذفه',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التراجع عن مسير الرواتب بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'تم التراجع عن مسير الرواتب بنجاح، يمكنك الآن إعادة توليد المسودة وتعديلها.',
                'data' => null
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ لا يمكن التراجع لوجود سندات صرف مرتبطة بالمحاسبة',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Cannot rollback payroll run that already has generated vouchers.'
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 مسير الرواتب غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Record not found.'
            ]
        )
    )]
    public function destroy(int $id)
    {
        try {
            $this->payrollService->rollbackPayrollRun($id);
            return $this->successResponse(null, __('تم التراجع عن مسير الرواتب بنجاح، يمكنك الآن إعادة توليد المسودة وتعديلها.'));
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $code);
        }
    }
}
