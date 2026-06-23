<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Http\Requests\StoreReconciliationRequest;
use Modules\Accounting\Models\AccReconciliation;
use Modules\Accounting\Models\AccSafe;
use Modules\Accounting\Services\LedgerService;
use Modules\Shared\Traits\SuccessResponseTrait;

class ReconciliationController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected LedgerService $ledgerService) {}

    public function index()
    {
        try {
            $records = AccReconciliation::with('safe', 'period')
                ->orderBy('reconciled_at', 'desc')->get();
            return $this->successResponse($records, 'تم جلب سجلات التسوية');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StoreReconciliationRequest $request)
    {
        try {
            $safe    = AccSafe::with('account')->findOrFail($request->safe_id);
            $balance = $this->ledgerService->getAccountBalance($safe->account_id);

            $record = AccReconciliation::create([
                'safe_id'              => $request->safe_id,
                'period_id'            => $request->period_id,
                'system_balance_usd'   => $balance['balance_usd'],
                'physical_balance_usd' => $request->physical_balance_usd,
                'system_balance_syp'   => $balance['balance_syp'] ?? 0,
                'physical_balance_syp' => $request->physical_balance_syp,
                'reconciled_by'        => Auth::id(),
                'reconciled_at'        => now(),
                'notes'                => $request->notes,
            ]);

            return $this->successResponse(
                $record->load('safe', 'period'),
                'تم حفظ التسوية بنجاح',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $record = AccReconciliation::with('safe', 'period')->findOrFail($id);
            return $this->successResponse($record, 'تم جلب بيانات التسوية');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
