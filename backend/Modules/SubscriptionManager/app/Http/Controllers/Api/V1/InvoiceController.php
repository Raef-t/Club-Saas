<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Models\Invoice;
use Modules\SubscriptionManager\Http\Resources\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class InvoiceController extends BaseController
{
    #[OA\Get(
        path: '/v1/my-invoices',
        summary: '💳 عرض فواتير العضو المصادق عليه مع أرقام الوصولات',
        description: 'استرجاع جميع فواتير العضو المصادق عليه متضمنة التفاصيل الإجمالية، أرقام الوصولات (العام، الكوتش، والنادي)، وحالة التدريب الخاص مع قائمة الدفعات.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع قائمة فواتير العضو بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Invoices retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total_paid', type: 'number', format: 'float', example: 1500.00),
                        new OA\Property(property: 'total_remaining', type: 'number', format: 'float', example: 200.00),
                        new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 1700.00),
                        new OA\Property(property: 'currency', type: 'string', example: 'SYP'),
                        new OA\Property(property: 'currency_type', type: 'string', example: 'SYP'),
                        new OA\Property(
                            property: 'invoices',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'code', type: 'string', nullable: true, example: 'INV_123456789'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date', example: '2026-09-12'),
                                    new OA\Property(property: 'subscription_name', type: 'string', example: 'تدريب خاص - كوتش أحمد'),
                                    new OA\Property(property: 'total', type: 'number', format: 'float', example: 500.00),
                                    new OA\Property(property: 'paid_amount', type: 'number', format: 'float', example: 500.00),
                                    new OA\Property(property: 'remaining_amount', type: 'number', format: 'float', example: 0.00),
                                    new OA\Property(property: 'currency', type: 'string', example: 'USD'),
                                    new OA\Property(property: 'currency_type', type: 'string', example: 'USD'),
                                    new OA\Property(property: 'is_private', type: 'boolean', example: true, description: 'هل الفاتورة لاشتراك تدريب خاص'),
                                    new OA\Property(property: 'receipt_number', type: 'string', nullable: true, example: 'REC-CLUB-999', description: 'رقم الوصل العام / أحدث وصل'),
                                    new OA\Property(property: 'coach_receipt_number', type: 'string', nullable: true, example: 'REC-COACH-888', description: 'رقم وصل الكوتش (في حال التدريب الخاص)'),
                                    new OA\Property(property: 'branch_receipt_number', type: 'string', nullable: true, example: 'REC-CLUB-999', description: 'رقم وصل النادي'),
                                    new OA\Property(
                                        property: 'payments',
                                        type: 'array',
                                        description: 'سجل الدفعات التابعة للفاتورة',
                                        items: new OA\Items(
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'receipt_number', type: 'string', nullable: true, example: 'REC-COACH-888'),
                                                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 300.00),
                                                new OA\Property(property: 'is_coach_payment', type: 'boolean', example: true, description: 'true لدفعة الكوتش، false لدفعة النادي'),
                                                new OA\Property(property: 'payment_method', type: 'string', nullable: true, example: 'cash'),
                                                new OA\Property(property: 'paid_at', type: 'string', format: 'date', nullable: true, example: '2026-09-12')
                                            ]
                                        )
                                    )
                                ]
                            )
                        )
                    ]
                )
            ],
            examples: [
                new OA\Examples(
                    example: 'Private Training Invoice',
                    summary: 'مثال: فاتورة تدريب خاص (وصل كوتش + وصل نادي)',
                    value: [
                        'status' => 'success',
                        'message' => 'Invoices retrieved successfully',
                        'data' => [
                            'total_paid' => 500.0,
                            'total_remaining' => 0.0,
                            'total_amount' => 500.0,
                            'currency' => 'USD',
                            'currency_type' => 'USD',
                            'invoices' => [
                                [
                                    'code' => 'INV_202609001',
                                    'created_at' => '2026-09-12',
                                    'subscription_name' => 'تدريب خاص - كوتش أحمد',
                                    'total' => 500.0,
                                    'paid_amount' => 500.0,
                                    'remaining_amount' => 0.0,
                                    'currency' => 'USD',
                                    'currency_type' => 'USD',
                                    'is_private' => true,
                                    'receipt_number' => 'REC-CLUB-999',
                                    'coach_receipt_number' => 'REC-COACH-888',
                                    'branch_receipt_number' => 'REC-CLUB-999',
                                    'payments' => [
                                        [
                                            'receipt_number' => 'REC-COACH-888',
                                            'amount' => 300.0,
                                            'is_coach_payment' => true,
                                            'payment_method' => 'cash',
                                            'paid_at' => '2026-09-12',
                                        ],
                                        [
                                            'receipt_number' => 'REC-CLUB-999',
                                            'amount' => 200.0,
                                            'is_coach_payment' => false,
                                            'payment_method' => 'cash',
                                            'paid_at' => '2026-09-12',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                new OA\Examples(
                    example: 'Regular Subscription Invoice',
                    summary: 'مثال: فاتورة اشتراك عام عادي',
                    value: [
                        'status' => 'success',
                        'message' => 'Invoices retrieved successfully',
                        'data' => [
                            'total_paid' => 1000.0,
                            'total_remaining' => 200.0,
                            'total_amount' => 1200.0,
                            'currency' => 'SYP',
                            'currency_type' => 'SYP',
                            'invoices' => [
                                [
                                    'code' => 'INV_202609002',
                                    'created_at' => '2026-09-10',
                                    'subscription_name' => 'اشتراك فتنس عام',
                                    'total' => 1200.0,
                                    'paid_amount' => 1000.0,
                                    'remaining_amount' => 200.0,
                                    'currency' => 'SYP',
                                    'currency_type' => 'SYP',
                                    'is_private' => false,
                                    'receipt_number' => 'REC-SUB-1045',
                                    'coach_receipt_number' => null,
                                    'branch_receipt_number' => 'REC-SUB-1045',
                                    'payments' => [
                                        [
                                            'receipt_number' => 'REC-SUB-1045',
                                            'amount' => 1000.0,
                                            'is_coach_payment' => false,
                                            'payment_method' => 'cash',
                                            'paid_at' => '2026-09-10',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: '🚫 الملف الشخصي للعضو غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Member profile not found.'
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح - رمز المرور مفقود أو غير صالح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function myInvoices(Request $request)
    {
        $user = $request->user();
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        $invoices = Invoice::with(['payments', 'subscription.plan', 'subscription.revenueSplit'])
            ->where('member_id', $member->id)
            ->orderByDesc('created_at')
            ->get();

        $total_paid = 0;
        $total_amount = 0;

        $invoicesData = $invoices->map(function ($invoice) use (&$total_paid, &$total_amount) {
            $paid = $invoice->payments->sum('amount');
            $total_paid += $paid;
            $total_amount += $invoice->total;

            $invoiceCurrency = $invoice->currency ?? ($invoice->subscription?->currency ?? 'SYP');
            $subscription = $invoice->subscription;

            $coachReceiptNumber = $subscription?->coach_receipt_number
                ?? $subscription?->revenueSplit?->coach_receipt_number;

            $branchReceiptNumber = $subscription?->branch_receipt_number
                ?? $subscription?->revenueSplit?->branch_receipt_number;

            // Check payments for coach/branch receipt numbers if not found on subscription
            if (!$coachReceiptNumber) {
                $coachPayment = $invoice->payments->firstWhere('reason', 'دفعة اشتراك المدرب');
                $coachReceiptNumber = $coachPayment?->receipt_number;
            }

            if (!$branchReceiptNumber) {
                $branchPayment = $invoice->payments->firstWhere('reason', 'دفعة اشتراك النادي');
                $branchReceiptNumber = $branchPayment?->receipt_number;
            }

            $isPrivate = !empty($coachReceiptNumber)
                || ($subscription && $subscription->revenueSplit !== null)
                || ($subscription && $subscription->plan && (float) $subscription->plan->coach_price > 0)
                || $invoice->payments->contains(fn($p) => $p->reason === 'دفعة اشتراك المدرب');

            $latestPaymentReceipt = $invoice->payments->sortByDesc('id')->first()?->receipt_number;
            $generalReceiptNumber = $latestPaymentReceipt ?? $branchReceiptNumber;

            if (!$isPrivate && empty($branchReceiptNumber)) {
                $branchReceiptNumber = $latestPaymentReceipt;
            }

            $paymentsData = $invoice->payments->map(function ($payment) use ($coachReceiptNumber) {
                $isCoachPayment = ($payment->reason === 'دفعة اشتراك المدرب')
                    || (!empty($coachReceiptNumber) && $payment->receipt_number === $coachReceiptNumber);

                return [
                    'receipt_number' => $payment->receipt_number,
                    'amount' => (float) $payment->amount,
                    'is_coach_payment' => (bool) $isCoachPayment,
                    'payment_method' => $payment->payment_method,
                    'paid_at' => $payment->created_at?->toDateString(),
                ];
            })->values();

            return [
                'code' => $invoice->code,
                'created_at' => $invoice->created_at?->toDateString(),
                'subscription_name' => $subscription?->plan?->name ?? 'N/A',
                'total' => (float) $invoice->total,
                'paid_amount' => (float) $paid,
                'remaining_amount' => (float) max(0, $invoice->total - $paid),
                'currency' => $invoiceCurrency,
                'currency_type' => $invoiceCurrency,
                'is_private' => (bool) $isPrivate,
                'receipt_number' => $generalReceiptNumber,
                'coach_receipt_number' => $coachReceiptNumber,
                'branch_receipt_number' => $branchReceiptNumber,
                'payments' => $paymentsData,
            ];
        });

        $total_remaining = max(0, $total_amount - $total_paid);

        return $this->successResponse([
            'total_paid' => (float) $total_paid,
            'total_remaining' => (float) $total_remaining,
            'total_amount' => (float) $total_amount,
            'currency' => 'SYP',
            'currency_type' => 'SYP',
            'invoices' => $invoicesData
        ], __('Invoices retrieved successfully'));
    }

    /**
     * Resolve the Member record from the authenticated user.
     */
    protected function resolveMember($user): ?object
    {
        if ($user instanceof \Modules\MemberManager\Models\Member) {
            return $user;
        }

        if (isset($user->person_id)) {
            return DB::table('members')
                ->where('person_id', $user->person_id)
                ->first();
        }

        return null;
    }
}
