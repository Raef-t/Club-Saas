<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StoreAccountRequest;
use Modules\Accounting\Http\Requests\UpdateAccountRequest;
use Modules\Accounting\Http\Resources\AccAccountResource;
use Modules\Accounting\Models\AccAccount;
use Modules\Accounting\Services\LedgerService;
use Modules\Shared\Traits\SuccessResponseTrait;

class AccountController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected LedgerService $ledgerService) {}

    public function index(Request $request)
    {
        try {
            $query = AccAccount::with('children');
            if ($request->has('type'))      $query->where('type', $request->type);
            if ($request->has('is_active')) $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            if ($request->has('parent_id') && $request->parent_id !== 'all') {
                $query->where('parent_id', $request->parent_id);
            } elseif (!$request->has('parent_id') && !$request->has('type')) {
                $query->whereNull('parent_id');
            }
            $accounts = $query->orderBy('code')->get();
            return $this->successResponse(AccAccountResource::collection($accounts), 'تم جلب دليل الحسابات');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StoreAccountRequest $request)
    {
        try {
            $account = AccAccount::create($request->validated());
            return $this->successResponse(new AccAccountResource($account), 'تم إضافة الحساب بنجاح', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $account = AccAccount::with('children', 'parent')->findOrFail($id);
            return $this->successResponse(new AccAccountResource($account), 'تم جلب بيانات الحساب');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function update(UpdateAccountRequest $request, $id)
    {
        try {
            $account = AccAccount::findOrFail($id);
            $account->update($request->validated());
            return $this->successResponse(new AccAccountResource($account), 'تم تحديث الحساب بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function ledger(Request $request, $id)
    {
        try {
            $from = $request->get('from', now()->startOfMonth()->toDateString());
            $to   = $request->get('to', now()->toDateString());
            $data = $this->ledgerService->getLedgerCard((int) $id, $from, $to);
            return $this->successResponse($data, 'تم جلب كشف الحساب');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
