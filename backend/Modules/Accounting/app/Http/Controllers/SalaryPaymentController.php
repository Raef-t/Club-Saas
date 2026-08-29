<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Http\Requests\StoreSalaryPaymentRequest;
use Modules\Accounting\Http\Resources\AccSalaryPaymentResource;
use Modules\Accounting\Models\AccAccount;
use Modules\Accounting\Models\AccJournal;
use Modules\Accounting\Models\AccPeriod;
use Modules\Accounting\Models\AccSafe;
use Modules\Accounting\Models\AccSalaryPayment;
use Modules\Accounting\Services\LedgerService;
use Modules\StaffManager\Models\Staff;
use Modules\Shared\Traits\SuccessResponseTrait;
use OpenApi\Attributes as OA;

class SalaryPaymentController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected LedgerService $ledgerService) {}

    #[OA\Get(
        path: '/accounting/salary-payments',
        summary: '📋 عرض قائمة مدفوعات الرواتب للكوادر',
        description: 'يسترجع قائمة بمدفوعات الرواتب المصروفة لكوادر النادي عبر الصناديق المالية مع دعم التصفية حسب الفرع والفترة المالية والبحث.',
        tags: ['Accounting - رواتب الكوادر والموظفين'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'تم جلب مدفوعات الرواتب بنجاح')]
    public function index(Request $request)
    {
        try {
            $branchId = $request->header('X-Branch-ID') ?: $request->input('branch_id');
            $periodId = $request->input('period_id');
            $search   = $request->input('search');

            $query = AccSalaryPayment::with(['staff.person', 'safe', 'period']);

            if ($branchId && $branchId !== 'all') {
                $query->whereHas('safe', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            if ($periodId) {
                $query->where('period_id', $periodId);
            }

            if ($search) {
                $query->whereHas('staff.person', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 15);
            $payments = $query->orderBy('date', 'desc')->paginate($perPage);

            return $this->successResponse([
                'payments' => AccSalaryPaymentResource::collection($payments),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page'    => $payments->lastPage(),
                    'per_page'     => $payments->perPage(),
                    'total'        => $payments->total(),
                ]
            ], 'تم جلب مدفوعات الرواتب بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: '/accounting/salary-payments',
        summary: '💵 صرف وتسجيل راتب جديد لكادر النادي',
        description: 'يقوم بصرف راتب لموظف أو مدرب من صندوق محدد وتوليد سند صرف (PV) وقيد محاسبي مزدوج تلقائياً.',
        tags: ['Accounting - رواتب الكوادر والموظفين'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 201, description: 'تم تسجيل وصرف الراتب بنجاح')]
    public function store(StoreSalaryPaymentRequest $request)
    {
        try {
            $data = $request->validated();

            $staff  = Staff::with('person')->findOrFail($data['staff_id']);
            $safe   = AccSafe::findOrFail($data['safe_id']);
            $period = AccPeriod::findOrFail($data['period_id']);

            if ($period->isClosed()) {
                return $this->error('الفترة المالية مغلقة، لا يمكن تسجيل رواتب بها.', 400);
            }

            // Determine safe currency and amount
            $currency = $safe->currency;
            $amount   = (float)$data['amount'];

            // Determine expense account based on staff role (coach / staff)
            $code = ($staff->role === 'coach') ? '5101' : '5102';
            $expenseAccount = AccAccount::where('code', $code)->first()
                ?? AccAccount::where('code', '5100')->first();

            if (!$expenseAccount) {
                return $this->error('حساب مصاريف الرواتب غير موجود في شجرة الحسابات.', 400);
            }

            $personName = $staff->person ? trim($staff->person->first_name . ' ' . $staff->person->last_name) : ('الكادر #' . $staff->id);

            $salaryPayment = DB::transaction(function () use ($data, $staff, $safe, $period, $currency, $amount, $expenseAccount, $personName) {
                // 1. Create the salary payment record
                $payment = AccSalaryPayment::create([
                    'staff_id'   => $staff->id,
                    'safe_id'    => $safe->id,
                    'period_id'  => $period->id,
                    'payslip_id' => $data['payslip_id'] ?? null,
                    'amount'     => $amount,
                    'currency'   => $currency,
                    'date'       => $data['date'],
                    'notes'      => $data['notes'] ?? null,
                ]);

                $slipInfo = !empty($data['payslip_id']) ? " (قسيمة #{$data['payslip_id']})" : "";

                // 2. Prepare Double-Entry Lines
                $debitLine = [
                    'account_id' => $expenseAccount->id,
                    'debit_usd'  => ($currency === 'USD') ? $amount : 0,
                    'credit_usd' => 0,
                    'debit_syp'  => ($currency === 'SYP') ? $amount : 0,
                    'credit_syp' => 0,
                    'memo'       => "راتب الموظف/المدرب: {$personName}{$slipInfo} — لشهر {$period->name}",
                ];

                $creditLine = [
                    'account_id' => $safe->account_id,
                    'debit_usd'  => 0,
                    'credit_usd' => ($currency === 'USD') ? $amount : 0,
                    'debit_syp'  => 0,
                    'credit_syp' => ($currency === 'SYP') ? $amount : 0,
                    'memo'       => "صرف راتب من صندوق: {$safe->name}",
                ];

                // 3. Post Journal Entry
                $journal = $this->ledgerService->postJournal(
                    header: [
                        'type'        => 'PV', // Payment Voucher
                        'date'        => $data['date'],
                        'description' => "صرف راتب: {$personName}{$slipInfo} — لشهر {$period->name}",
                        'safe_id'     => $safe->id,
                        'source_type' => 'SalaryPayments',
                        'source_id'   => $payment->id,
                        'notes'       => $data['notes'] ?? null,
                        'period_id'   => $period->id,
                        'branch_id'   => $safe->branch_id,
                    ],
                    lines: [$debitLine, $creditLine],
                    postImmediately: true
                );

                // 4. Update payment with journal ID
                $payment->update(['journal_id' => $journal->id]);

                return $payment;
            });

            return $this->successResponse(
                new AccSalaryPaymentResource($salaryPayment->load(['staff.person', 'safe', 'period', 'payslip'])),
                'تم تسجيل وصرف الراتب بنجاح',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Delete(
        path: '/accounting/salary-payments/{id}',
        summary: '🗑️ إلغاء دفعة راتب وسند الصرف المرتبط بها',
        description: 'يلغي حركة صرف الراتب ويقوم بإلغاء سند الصرف المحاسبي المولد تلقائياً.',
        tags: ['Accounting - رواتب الكوادر والموظفين'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'تم إلغاء دفعة الراتب بنجاح')]
    public function destroy($id)
    {
        try {
            $payment = AccSalaryPayment::findOrFail($id);

            if ($payment->period && $payment->period->isClosed()) {
                return $this->error('الفترة المالية مغلقة، لا يمكن إلغاء مدفوعات رواتب بها.', 400);
            }

            DB::transaction(function () use ($payment) {
                // If there's an associated journal, void/cancel it
                if ($payment->journal_id) {
                    $journal = AccJournal::find($payment->journal_id);
                    if ($journal && $journal->isPosted()) {
                        $this->ledgerService->cancelJournal($journal, 'إلغاء دفعة الراتب رقم #' . $payment->id);
                    }
                }
                
                // Delete the payment record
                $payment->delete();
            });

            return $this->successResponse(null, 'تم إلغاء دفعة الراتب بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
