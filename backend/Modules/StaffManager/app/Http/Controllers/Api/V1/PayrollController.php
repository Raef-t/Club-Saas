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
        description: 'استرجاع قائمة بجميع مسيرات الرواتب المُنفذة في النظام.',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (الافتراضي: 15)', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع مسيرات الرواتب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Payroll runs retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $perPage = $this->getPerPage($request);
        $runs = $this->payrollService->getAllPayrollRuns($perPage);
        return $this->successResponse(PayrollRunResource::collection($runs), __('Payroll runs retrieved'));
    }

    #[OA\Post(
        path: '/v1/payroll-runs',
        summary: '➕ إنشاء مسير رواتب جديد',
        description: 'بدء تشغيل مسير رواتب لفترة محددة.',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['period_start', 'period_end'],
            properties: [
                new OA\Property(property: 'period_start', type: 'string', format: 'date', example: '2023-10-01'),
                new OA\Property(property: 'period_end', type: 'string', format: 'date', example: '2023-10-31')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء مسير الرواتب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Payroll run created'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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
        description: 'استرجاع تفاصيل مسير رواتب محدد مع إيصالات الدفع المرتبطة به.',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'payroll_run', in: 'path', required: true, description: 'معرف مسير الرواتب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل مسير الرواتب',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Payroll run retrieved'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 مسير الرواتب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show(int $id)
    {
        $run = $this->payrollService->getPayrollRunById($id);
        return $this->successResponse(new PayrollRunResource($run), __('Payroll run retrieved'));
    }

    #[OA\Post(
        path: '/v1/payroll-runs/{id}/generate-payslips',
        summary: '⚙️ توليد إيصالات الدفع',
        description: 'إنشاء إيصالات الدفع الفردية (Payslips) للموظفين ضمن مسير رواتب محدد.',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف مسير الرواتب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم توليد إيصالات الدفع بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Payslips generated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 مسير الرواتب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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
    #[OA\Parameter(name: 'payroll_run', in: 'path', required: true, description: 'معرف مسير الرواتب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم التراجع عن مسير الرواتب بنجاح')]
    #[OA\Response(response: 422, description: '⚠️ لا يمكن التراجع لوجود سندات صرف مرتبطة بالمحاسبة')]
    #[OA\Response(response: 404, description: '🚫 مسير الرواتب غير موجود')]
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
