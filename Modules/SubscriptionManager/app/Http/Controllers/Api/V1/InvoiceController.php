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
        summary: '💳 List authenticated member\'s invoices',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Invoices retrieved successfully')
        ]
    )]
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
