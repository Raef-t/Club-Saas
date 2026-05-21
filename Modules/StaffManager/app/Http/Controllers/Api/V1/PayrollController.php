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
        summary: '💰 List all payroll runs',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        $runs = $this->payrollService->getAllPayrollRuns();
        return $this->successResponse(PayrollRunResource::collection($runs), __('Payroll runs retrieved'));
    }

    #[OA\Post(
        path: '/v1/payroll-runs',
        summary: '➕ Create a new payroll run',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Payroll run created')
        ]
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
        path: '/v1/payroll-runs/{id}',
        summary: '🔍 Get payroll run details with payslips',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function show(int $id)
    {
        $run = $this->payrollService->getPayrollRunById($id);
        return $this->successResponse(new PayrollRunResource($run), __('Payroll run retrieved'));
    }

    #[OA\Post(
        path: '/v1/payroll-runs/{id}/generate-payslips',
        summary: '⚙️ Generate payslips for a payroll run',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Payslips generated')
        ]
    )]
    public function generatePayslips(int $id)
    {
        $run = $this->payrollService->generatePayslips($id);
        return $this->successResponse(new PayrollRunResource($run), __('Payslips generated successfully'));
    }

    #[OA\Post(
        path: '/v1/payroll-runs/{id}/approve',
        summary: '✅ Approve a payroll run',
        tags: ['Payroll Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Payroll run approved')
        ]
    )]
    public function approve(int $id)
    {
        $run = $this->payrollService->approvePayrollRun($id);
        return $this->successResponse(new PayrollRunResource($run), __('Payroll run approved'));
    }
}
