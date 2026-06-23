<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Services\ReportService;
use Modules\Shared\Traits\SuccessResponseTrait;

class ReportController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected ReportService $reportService) {}

    public function trialBalance(Request $request)
    {
        try {
            $request->validate(['period_id' => 'required|integer|exists:acc_periods,id']);
            $data = $this->reportService->getTrialBalance((int) $request->period_id);
            return $this->successResponse($data, 'تم جلب ميزان المراجعة');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function incomeStatement(Request $request)
    {
        try {
            $request->validate(['period_id' => 'required|integer|exists:acc_periods,id']);
            $data = $this->reportService->getIncomeStatement((int) $request->period_id);
            return $this->successResponse($data, 'تم جلب قائمة الدخل');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function balanceSheet(Request $request)
    {
        try {
            $request->validate(['period_id' => 'required|integer|exists:acc_periods,id']);
            $data = $this->reportService->getBalanceSheet((int) $request->period_id);
            return $this->successResponse($data, 'تم جلب الميزانية العمومية');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
