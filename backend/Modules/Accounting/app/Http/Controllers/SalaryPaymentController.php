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

            $query = AccSalaryPayment::with(['staff.person', 'safe', 'period', 'payslip']);

            if ($branchId && $branchId !== 'all') {
                $query->whereHas('safe', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            if ($periodId && $periodId !== 'all') {
                $query->where('period_id', $periodId);
            }

            if ($search) {
                $query->whereHas('staff.person', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 25);
            $payments = $query->orderBy('date', 'desc')->paginate($perPage);

            return $this->successResponse(
                AccSalaryPaymentResource::collection($payments)->response()->getData(true),
                'تم جلب مدفوعات الرواتب بنجاح'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: '/accounting/salary-payments',
        summary: '💵 صرف وتسجيل راتب جديد لكادر النادي',
        description: 'يقوم بصرف راتب أو سلفة أو مكافأة لموظف أو مدرب من صندوق محدد وتوليد سند صرف (PV) وقيد محاسبي مزدوج تلقائياً وتحديث حالة القسيمة المرتبطة إلى مدفوعة.',
        tags: ['Accounting - رواتب الكوادر والموظفين'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['staff_id', 'safe_id', 'period_id', 'amount', 'date'],
            properties: [
                new OA\Property(property: 'staff_id', type: 'integer', example: 5, description: 'معرف الكادر'),
                new OA\Property(property: 'safe_id', type: 'integer', example: 2, description: 'معرف الصندوق المالي المصروف منه'),
                new OA\Property(property: 'period_id', type: 'integer', example: 1, description: 'معرف الفترة المالية'),
                new OA\Property(property: 'payslip_id', type: 'integer', nullable: true, example: 12, description: 'معرف قسيمة الراتب المعتمدة إن وجدت'),
                new OA\Property(property: 'payment_type', type: 'string', enum: ['salary', 'advance', 'bonus'], example: 'salary', description: 'نوع الدفعة: راتب مسير، سلفة، مكافأة'),
                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 500000, description: 'المبلغ المصروف'),
                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-08-29', description: 'تاريخ الصرف'),
                new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'صرف مستحقات شهر آب')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'تم تسجيل وصرف المستحقات بنجاح')]
    public function store(StoreSalaryPaymentRequest $request)
    {
        try {
            $data = $request->validated();

            $staff  = Staff::with('person')->findOrFail($data['staff_id']);
            $safe   = AccSafe::findOrFail($data['safe_id']);
            $period = !empty($data['period_id'])
                ? AccPeriod::findOrFail($data['period_id'])
                : (AccPeriod::where('status', 'open')->where('start_date', '<=', $data['date'])->where('end_date', '>=', $data['date'])->first()
                    ?? AccPeriod::where('status', 'open')->first());

            if (!$period) {
                return $this->error('لا توجد فترة مالية مفتوحة لتسجيل هذا الصرف.', 400);
            }

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

            $paymentType = $data['payment_type'] ?? 'salary';
            $typeLabel = match($paymentType) {
                'advance' => 'سلفة',
                'bonus'   => 'مكافأة',
                default   => 'راتب',
            };

            // Strict validation when paying an approved payslip
            if (!empty($data['payslip_id']) && $paymentType === 'salary') {
                $payslip = \Modules\StaffManager\Models\Payslip::findOrFail($data['payslip_id']);

                if ($payslip->status === 'paid') {
                    return $this->error('قسيمة الراتب المحددة تم صرفها مسبقاً في النظام.', 422);
                }

                if ((int)$payslip->staff_id !== (int)$staff->id) {
                    return $this->error('قسيمة الراتب المحددة لا تنتمي إلى هذا الكادر.', 422);
                }

                if (round($amount, 2) !== round((float)$payslip->net_pay, 2)) {
                    $expected = number_format((float)$payslip->net_pay, 2);
                    return $this->error("المبلغ المصروف يجب أن يطابق تماماً صافي القسيمة المعتمدة ({$expected} {$currency}).", 422);
                }
            }

            $salaryPayment = DB::transaction(function () use ($data, $staff, $safe, $period, $currency, $amount, $expenseAccount, $personName, $paymentType, $typeLabel) {
                // 1. Create the salary payment record
                $payment = AccSalaryPayment::create([
                    'staff_id'     => $staff->id,
                    'safe_id'      => $safe->id,
                    'period_id'    => $period->id,
                    'payslip_id'   => $data['payslip_id'] ?? null,
                    'payment_type' => $paymentType,
                    'amount'       => $amount,
                    'currency'     => $currency,
                    'date'         => $data['date'],
                    'notes'        => $data['notes'] ?? null,
                ]);

                // 2. If linked to a payslip, mark payslip as paid
                if (!empty($data['payslip_id'])) {
                    $payslip = \Modules\StaffManager\Models\Payslip::find($data['payslip_id']);
                    if ($payslip) {
                        $payslip->update([
                            'status'  => 'paid',
                            'paid_at' => $data['date'],
                        ]);
                    }
                }

                $slipInfo = !empty($data['payslip_id']) ? " (قسيمة #{$data['payslip_id']})" : "";

                // 3. Prepare Double-Entry Lines
                $debitLine = [
                    'account_id' => $expenseAccount->id,
                    'debit_usd'  => ($currency === 'USD') ? $amount : 0,
                    'credit_usd' => 0,
                    'debit_syp'  => ($currency === 'SYP') ? $amount : 0,
                    'credit_syp' => 0,
                    'memo'       => "{$typeLabel} الموظف/المدرب: {$personName}{$slipInfo} — لشهر {$period->name}",
                ];

                $creditLine = [
                    'account_id' => $safe->account_id,
                    'debit_usd'  => 0,
                    'credit_usd' => ($currency === 'USD') ? $amount : 0,
                    'debit_syp'  => 0,
                    'credit_syp' => ($currency === 'SYP') ? $amount : 0,
                    'memo'       => "صرف {$typeLabel} من صندوق: {$safe->name}",
                ];

                // 4. Post Journal Entry
                $journal = $this->ledgerService->postJournal(
                    header: [
                        'type'        => 'PV', // Payment Voucher
                        'date'        => $data['date'],
                        'description' => "صرف {$typeLabel}: {$personName}{$slipInfo} — لشهر {$period->name}",
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

                // 5. Update payment with journal ID
                $payment->update(['journal_id' => $journal->id]);

                return $payment;
            });

            return $this->successResponse(
                new AccSalaryPaymentResource($salaryPayment->load(['staff.person', 'safe', 'period', 'payslip'])),
                'تم تسجيل وصرف المستحقات بنجاح',
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

                $payslipId = $payment->payslip_id;

                // Delete the payment record
                $payment->delete();

                // If linked to a payslip, check if any other active payment exists
                if ($payslipId) {
                    $remainingCount = AccSalaryPayment::where('payslip_id', $payslipId)->count();
                    if ($remainingCount === 0) {
                        $payslip = \Modules\StaffManager\Models\Payslip::find($payslipId);
                        if ($payslip) {
                            $payslip->update([
                                'status'  => 'pending',
                                'paid_at' => null,
                            ]);
                        }
                    }
                }
            });

            return $this->successResponse(null, 'تم إلغاء دفعة الراتب بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
