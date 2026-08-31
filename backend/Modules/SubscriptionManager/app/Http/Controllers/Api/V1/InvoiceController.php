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
        summary: '💳 فواتيري',
        description: 'استرجاع جميع فواتير العضو المصادق عليه مع تفاصيل المدفوعات الإجمالية والتفصيلية.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الفواتير بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Invoices retrieved successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'total_paid', type: 'number', format: 'float', example: 1000.00),
                    new OA\Property(property: 'total_remaining', type: 'number', format: 'float', example: 500.00),
                    new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 1500.00),
                    new OA\Property(property: 'invoices', type: 'array', items: new OA\Items(type: 'object', properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'INV_123456789'),
                        new OA\Property(property: 'created_at', type: 'string', example: '2026-06-30'),
                        new OA\Property(property: 'paid_amount', type: 'number', format: 'float', example: 500.00),
                        new OA\Property(property: 'subscription_name', type: 'string', example: 'Gold Plan'),
                        new OA\Property(property: 'total', type: 'number', format: 'float', example: 1000.00),
                        new OA\Property(property: 'remaining_amount', type: 'number', format: 'float', example: 500.00)
                    ]))
                ])
            ]
        )
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (الافتراضي: 15)', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 403, description: '🚫 الملف الشخصي غير موجود')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function myInvoices(Request $request)
    {
        $user = $request->user();
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        $baseQuery = Invoice::with(['payments', 'subscription.plan'])
            ->where('member_id', $member->id);

        $allInvoices = (clone $baseQuery)->get();
        $total_paid = 0;
        $total_amount = 0;
        foreach ($allInvoices as $inv) {
            $total_paid += $inv->payments->sum('amount');
            $total_amount += $inv->total;
        }
        $total_remaining = max(0, $total_amount - $total_paid);

        $perPage = $this->getPerPage($request);
        $paginator = $baseQuery->orderByDesc('created_at')->paginate($perPage);

        $invoicesData = collect($paginator->items())->map(function ($invoice) {
            $paid = $invoice->payments->sum('amount');
            return [
                'code' => $invoice->code,
                'created_at' => $invoice->created_at?->toDateString(),
                'paid_amount' => (float) $paid,
                'subscription_name' => $invoice->subscription?->plan?->name ?? 'N/A',
                'total' => (float) $invoice->total,
                'remaining_amount' => (float) max(0, $invoice->total - $paid),
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => __('Invoices retrieved successfully'),
            'data' => [
                'total_paid' => (float) $total_paid,
                'total_remaining' => (float) $total_remaining,
                'total_amount' => (float) $total_amount,
                'invoices' => $invoicesData,
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ]
        ]);
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
