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
        summary: '💳 عرض فواتير العضو المصادق عليه',
        description: 'استرجاع جميع فواتير العضو المصادق عليه متضمنة التفاصيل الإجمالية (إجمالي المدفوع، المتبقي، وإجمالي الفواتير).',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع قائمة فواتير العضو بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Invoices retrieved successfully',
                'data' => [
                    'total_paid' => 1000.00,
                    'total_remaining' => 500.00,
                    'total_amount' => 1500.00,
                    'invoices' => [
                        [
                            'code' => 'INV_123456789',
                            'created_at' => '2026-06-30',
                            'paid_amount' => 500.00,
                            'subscription_name' => 'الاشتراك الذهبي - Gold Plan',
                            'total' => 1000.00,
                            'remaining_amount' => 500.00
                        ]
                    ]
                ]
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

        $invoices = Invoice::with(['payments', 'subscription.plan'])
            ->where('member_id', $member->id)
            ->orderByDesc('created_at')
            ->get();

        $total_paid = 0;
        $total_amount = 0;

        $invoicesData = $invoices->map(function ($invoice) use (&$total_paid, &$total_amount) {
            $paid = $invoice->payments->sum('amount');
            $total_paid += $paid;
            $total_amount += $invoice->total;

            return [
                'code' => $invoice->code,
                'created_at' => $invoice->created_at?->toDateString(),
                'paid_amount' => (float) $paid,
                'subscription_name' => $invoice->subscription?->plan?->name ?? 'N/A',
                'total' => (float) $invoice->total,
                'remaining_amount' => (float) max(0, $invoice->total - $paid),
            ];
        });

        $total_remaining = max(0, $total_amount - $total_paid);

        return $this->successResponse([
            'total_paid' => (float) $total_paid,
            'total_remaining' => (float) $total_remaining,
            'total_amount' => (float) $total_amount,
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
