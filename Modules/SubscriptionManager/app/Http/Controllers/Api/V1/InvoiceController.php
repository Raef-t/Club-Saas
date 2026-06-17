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
        description: 'استرجاع جميع فواتير العضو المصادق عليه مع تفاصيل المدفوعات.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الفواتير بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Invoices retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 500.00),
                    new OA\Property(property: 'status', type: 'string', example: 'paid')
                ]))
            ]
        )
    )]
    #[OA\Response(response: 403, description: '🚫 الملف الشخصي غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member profile not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function myInvoices(Request $request)
    {
        $user = $request->user();
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        $perPage = $request->input('per_page', 15);

        $invoices = Invoice::with('payments')
            ->where('member_id', $member->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->successResponse(
            InvoiceResource::collection($invoices)->response()->getData(true),
            __('Invoices retrieved successfully')
        );
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
                ->whereNull('deleted_at')
                ->first();
        }

        return null;
    }
}
