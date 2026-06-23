<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\ReverseJournalRequest;
use Modules\Accounting\Http\Requests\StoreJournalRequest;
use Modules\Accounting\Http\Resources\AccJournalResource;
use Modules\Accounting\Models\AccJournal;
use Modules\Accounting\Services\LedgerService;
use Modules\Shared\Traits\SuccessResponseTrait;

class JournalController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected LedgerService $ledgerService) {}

    public function index(Request $request)
    {
        try {
            $query = AccJournal::with('period', 'safe', 'counterparty', 'entries.account', 'reversesJournal');
            if ($request->has('type'))        $query->where('type', $request->type);
            if ($request->has('status'))      $query->where('status', $request->status);
            if ($request->has('safe_id'))     $query->where('safe_id', $request->safe_id);
            if ($request->has('period_id'))   $query->where('period_id', $request->period_id);
            if ($request->has('source_type')) $query->where('source_type', $request->source_type);
            if ($request->has('source_id'))   $query->where('source_id', $request->source_id);
            if ($request->has('from_date'))   $query->where('date', '>=', $request->from_date);
            if ($request->has('to_date'))     $query->where('date', '<=', $request->to_date);
            $journals = $query->orderBy('date', 'desc')->paginate($request->get('per_page', 20));
            return $this->successResponse($journals, 'تم جلب سندات القيود');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StoreJournalRequest $request)
    {
        try {
            $data    = $request->validated();
            $lines   = $data['lines'];
            $header  = collect($data)->except('lines')->toArray();
            $journal = $this->ledgerService->postJournal($header, $lines, false);
            return $this->successResponse(new AccJournalResource($journal), 'تم إنشاء سند القيد كمسودة', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show($id)
    {
        try {
            $journal = AccJournal::with('entries.account', 'period', 'safe', 'counterparty')->findOrFail($id);
            return $this->successResponse(new AccJournalResource($journal), 'تم جلب بيانات السند');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function post($id)
    {
        try {
            $journal = AccJournal::with('entries', 'period')->findOrFail($id);
            $journal = $this->ledgerService->postDraftJournal($journal);
            return $this->successResponse(new AccJournalResource($journal), 'تم ترحيل السند بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function reverse(ReverseJournalRequest $request, $id)
    {
        try {
            $journal         = AccJournal::with('entries')->findOrFail($id);
            $reversalJournal = $this->ledgerService->reverseJournal($journal, $request->validated('reason'));
            return $this->successResponse(new AccJournalResource($reversalJournal), 'تم عكس السند بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
