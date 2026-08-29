<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Services\ReportService;
use Modules\Shared\Traits\SuccessResponseTrait;

use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected ReportService $reportService) {}

    #[OA\Get(
        path: '/accounting/reports/trial-balance',
        summary: '📊 ميزان المراجعة المالي للفترة',
        description: 'يولد هذا التقرير ميزان المراجعة المالي بالكامل للفترة المحددة. يستعرض قائمة بكافة حسابات دليل الحسابات مع أرصدتها الافتتاحية، وإجمالي الحركات المدينة والدائنة خلال الفترة، والأرصدة الختامية للتحقق من توازن الأستاذ المالي المزدوج (مجموع المدين = مجموع الدائن).',
        tags: ['Accounting - التقارير والقوائم المالية الختامية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'period_id', in: 'query', required: true, description: 'معرف الفترة المالية المراد توليد التقرير لها', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب ميزان المراجعة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب ميزان المراجعة'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'period', type: 'object', description: 'معلومات الفترة المالية'),
                        new OA\Property(property: 'accounts', type: 'array', items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'account_id', type: 'integer', example: 1),
                                new OA\Property(property: 'account_code', type: 'string', example: '1101001'),
                                new OA\Property(property: 'account_name', type: 'string', example: 'صندوق الصالة الرئيسي'),
                                new OA\Property(property: 'debit_usd', type: 'number', example: 15000.00),
                                new OA\Property(property: 'credit_usd', type: 'number', example: 12000.00),
                                new OA\Property(property: 'debit_syp', type: 'number', example: 0.00),
                                new OA\Property(property: 'credit_syp', type: 'number', example: 0.00)
                            ]
                        )),
                        new OA\Property(property: 'totals', type: 'object', description: 'المجاميع الإجمالية لضمان توازن ميزان المراجعة')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ الفترة المحددة غير موجودة أو الحقل مطلوب')]
    public function trialBalance(Request $request)
    {
        try {
            $request->validate(['period_id' => 'required|integer|exists:acc_periods,id']);
            $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
            $data = $this->reportService->getTrialBalance((int) $request->period_id, $branchId);
            return $this->successResponse($data, 'تم جلب ميزان المراجعة');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[OA\Get(
        path: '/accounting/reports/income-statement',
        summary: '📈 قائمة الدخل (الأرباح والخسائر)',
        description: 'يولد هذا الإجراء قائمة الدخل للفترة المحددة. يقارن كافة إيرادات النادي الرياضي (اشتراكات، مبيعات منتجات) مع المصروفات (أجور، صيانة، إيجارات) لبيان صافي الربح أو الخسارة التشغيلية والنهائية للنادي.',
        tags: ['Accounting - التقارير والقوائم المالية الختامية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'period_id', in: 'query', required: true, description: 'معرف الفترة المالية المراد توليد قائمة الدخل لها', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب قائمة الدخل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب قائمة الدخل'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'revenues', type: 'array', items: new OA\Items(type: 'object', description: 'بنود الإيرادات وأرصدتها')),
                        new OA\Property(property: 'expenses', type: 'array', items: new OA\Items(type: 'object', description: 'بنود المصروفات وأرصدتها')),
                        new OA\Property(property: 'total_revenues_usd', type: 'number', example: 25000.00),
                        new OA\Property(property: 'total_expenses_usd', type: 'number', example: 12000.00),
                        new OA\Property(property: 'net_income_usd', type: 'number', description: 'صافي الربح أو الخسارة بالدولار', example: 13000.00)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ الفترة المحاسبية مطلوبة أو غير موجودة')]
    public function incomeStatement(Request $request)
    {
        try {
            $request->validate(['period_id' => 'required|integer|exists:acc_periods,id']);
            $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
            $data = $this->reportService->getIncomeStatement((int) $request->period_id, $branchId);
            return $this->successResponse($data, 'تم جلب قائمة الدخل');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[OA\Get(
        path: '/accounting/reports/balance-sheet',
        summary: '⚖️ الميزانية العمومية (قائمة المركز المالي)',
        description: 'يولد قائمة المركز المالي أو الميزانية العمومية في نهاية الفترة المحددة. تلخص هذه القائمة ما يملكه النادي من أصول (نقدية بالصناديق، حسابات بنكية، أجهزة رياضية وعقارات) مقابل التزاماته وحقوق الملكية للشركاء (رأس المال، الأرباح المحتجزة). ويجب أن تتوازن المعادلة: الأصول = الالتزامات + حقوق الملكية.',
        tags: ['Accounting - التقارير والقوائم المالية الختامية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'period_id', in: 'query', required: true, description: 'معرف الفترة المالية لتوليد الميزانية لها', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب الميزانية العمومية بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب الميزانية العمومية'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'assets', type: 'array', items: new OA\Items(type: 'object', description: 'بنود الأصول وقيمها الدفترية')),
                        new OA\Property(property: 'liabilities', type: 'array', items: new OA\Items(type: 'object', description: 'بنود الالتزامات')),
                        new OA\Property(property: 'equity', type: 'array', items: new OA\Items(type: 'object', description: 'بنود حقوق الملكية (رأس المال + أرباح الفترة)')),
                        new OA\Property(property: 'total_assets_usd', type: 'number', example: 85000.00),
                        new OA\Property(property: 'total_liabilities_and_equity_usd', type: 'number', example: 85000.00)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ الحقول المطلوبة غير صالحة أو مفقودة')]
    public function balanceSheet(Request $request)
    {
        try {
            $request->validate(['period_id' => 'required|integer|exists:acc_periods,id']);
            $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
            $data = $this->reportService->getBalanceSheet((int) $request->period_id, $branchId);
            return $this->successResponse($data, 'تم جلب الميزانية العمومية');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
