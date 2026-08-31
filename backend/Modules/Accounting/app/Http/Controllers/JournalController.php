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
use OpenApi\Attributes as OA;

class JournalController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected LedgerService $ledgerService) {}

    #[OA\Get(
        path: '/accounting/journals',
        summary: '🗂️ جلب وقراءة سندات القيود اليومية',
        description: 'يسترجع هذا الإجراء قائمة بكافة سندات القيد (سندات القبض، الصرف، القيود العامة) المعرفة بالنظام مع تفاصيل البنود الخاصة بكل قيد وحالته (مسودة Draft، أو مرحل Posted). يدعم التصفية حسب التاريخ، الفرع، الصندوق، أو نوع السند.',
        tags: ['Accounting - القيود اليومية العامة'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'type', in: 'query', required: false, description: 'نوع السند (JV = قيد عام، RV = قبض، PV = صرف)', schema: new OA\Schema(type: 'string', enum: ['JV', 'RV', 'PV']))]
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'حالة السند (draft = مسودة، posted = مرحل ومؤثر مالياً)', schema: new OA\Schema(type: 'string', enum: ['draft', 'posted']))]
    #[OA\Parameter(name: 'safe_id', in: 'query', required: false, description: 'معرف الصندوق المالي في حال كان السند سند قبض أو صرف مرتبط بصندوق', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'period_id', in: 'query', required: false, description: 'معرف الفترة المالية المرتبط بها القيد', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'from_date', in: 'query', required: false, description: 'بدءاً من هذا التاريخ (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to_date', in: 'query', required: false, description: 'حتى هذا التاريخ (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد السندات في الصفحة (الافتراضي 20)', schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Parameter(name: 'account_id', in: 'query', required: false, description: 'معرف الحساب المالي لتصفية القيود التي تحتوي على هذا الحساب', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'account_type', in: 'query', required: false, description: 'نوع الحساب (expense, revenue, asset, liability, equity)', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع قائمة السندات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب سندات القيود'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AccJournal')),
                        new OA\Property(property: 'total', type: 'integer', example: 120),
                        new OA\Property(
                            property: 'totals',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_amount_usd', type: 'number', example: 1500.00),
                                new OA\Property(property: 'total_amount_syp', type: 'number', example: 250000.00),
                                new OA\Property(property: 'total_count', type: 'integer', example: 12)
                            ]
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function index(Request $request)
    {
        try {
            $query = AccJournal::with('period', 'safe', 'counterparty', 'entries.account', 'reversesJournal');
            if ($request->filled('type') && $request->type !== 'all')        $query->where('type', $request->type);
            if ($request->filled('status') && $request->status !== 'all')    $query->where('status', $request->status);
            if ($request->filled('safe_id') && $request->safe_id !== 'all')  $query->where('safe_id', $request->safe_id);
            if ($request->filled('account_id') && $request->account_id !== 'all') {
                $accountId = $request->account_id;
                $query->whereHas('entries', fn($q) => $q->where('account_id', $accountId));
            }
            if ($request->filled('account_type') && $request->account_type !== 'all') {
                $accountType = $request->account_type;
                $query->whereHas('entries.account', fn($q) => $q->where('type', $accountType));
            }
            if ($request->filled('period_id')) $query->where('period_id', $request->period_id);
            $branchId = $request->header('X-Branch-ID') ?: $request->input('branch_id');
            if ($branchId && $branchId !== 'all') $query->where('branch_id', $branchId);
            if ($request->filled('source_type')) $query->where('source_type', $request->source_type);
            if ($request->filled('source_id'))   $query->where('source_id', $request->source_id);
            if ($request->filled('from_date'))   $query->where('date', '>=', $request->from_date);
            if ($request->filled('to_date'))     $query->where('date', '<=', $request->to_date);
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reference_number', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            // Calculate totals for matching query
            $totalsQuery = clone $query;
            $journalIds = $totalsQuery->pluck('id');

            $entriesQuery = \Illuminate\Support\Facades\DB::table('acc_journal_entries')
                ->whereIn('journal_id', $journalIds);

            if ($request->filled('account_id') && $request->account_id !== 'all') {
                $entriesQuery->where('account_id', $request->account_id);
            }

            $rawTotals = $entriesQuery->selectRaw('
                SUM(debit_usd) as total_debit_usd,
                SUM(credit_usd) as total_credit_usd,
                SUM(debit_syp) as total_debit_syp,
                SUM(credit_syp) as total_credit_syp
            ')->first();

            $isAccountFiltered = $request->filled('account_id') && $request->account_id !== 'all';
            $totalUsd = $isAccountFiltered
                ? (float)(($rawTotals->total_debit_usd ?? 0) + ($rawTotals->total_credit_usd ?? 0))
                : (float)($rawTotals->total_debit_usd ?? 0);
            $totalSyp = $isAccountFiltered
                ? (float)(($rawTotals->total_debit_syp ?? 0) + ($rawTotals->total_credit_syp ?? 0))
                : (float)($rawTotals->total_debit_syp ?? 0);

            $totals = [
                'total_amount_usd' => round($totalUsd, 2),
                'total_amount_syp' => round($totalSyp, 2),
                'total_count'      => $journalIds->count(),
            ];

            $journals = $query->orderBy('date', 'desc')->paginate($request->get('per_page', 25));
            $responseData = AccJournalResource::collection($journals)->response()->getData(true);
            $responseData['totals'] = $totals;

            return $this->successResponse($responseData, 'تم جلب سندات القيود');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: '/accounting/journals',
        summary: '➕ إنشاء سند قيد مالي جديد (مسودة)',
        description: 'ينشئ هذا الإجراء سند قيد مالي مزدوج جديد ويحفظه بحالة (مسودة Draft). السند لا يؤثر على ميزان المراجعة أو أرصدة الصناديق إلا بعد ترحيله. **هام:** يجب أن يكون مجموع الحسابات المدينة مساوياً لمجموع الحسابات الدائنة بكلتا العملتين لتفادي أخطاء عدم توازن القيد.',
        tags: ['Accounting - القيود اليومية العامة'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات رأس السند وتفاصيل البنود المحاسبية للمدخلات والمخرجات',
        content: new OA\JsonContent(
            required: ['type', 'date', 'description', 'lines'],
            properties: [
                new OA\Property(property: 'type', type: 'string', description: 'نوع السند (JV = قيد عام، RV = قبض، PV = صرف)', enum: ['JV', 'RV', 'PV'], example: 'JV'),
                new OA\Property(property: 'date', type: 'string', format: 'date', description: 'تاريخ الاستحقاق المحاسبي للقيد', example: '2026-06-23'),
                new OA\Property(property: 'description', type: 'string', description: 'البيان أو الوصف العام للسند المالي', example: 'إثبات مصروف الصيانة لشهر حزيران'),
                new OA\Property(property: 'branch_id', type: 'integer', description: 'معرف الفرع التابع له العملية المالية', example: 1),
                new OA\Property(property: 'safe_id', type: 'integer', description: 'معرف الصندوق في حال كان سند قبض أو صرف', nullable: true, example: null),
                new OA\Property(property: 'counterparty_id', type: 'integer', description: 'معرف الطرف الثالث (عميل/مورد/موظف)', nullable: true, example: null),
                new OA\Property(
                    property: 'lines',
                    type: 'array',
                    description: 'سطور القيد المحاسبي المزدوج (يجب أن تحتوي على سطرين على الأقل وتكون متوازنة)',
                    items: new OA\Items(
                        type: 'object',
                        required: ['account_id'],
                        properties: [
                            new OA\Property(property: 'account_id', type: 'integer', description: 'معرف الحساب المحاسبي من دليل الحسابات', example: 5),
                            new OA\Property(property: 'debit_usd', type: 'number', format: 'float', description: 'القيمة المدينة بالدولار', example: 100.00),
                            new OA\Property(property: 'credit_usd', type: 'number', format: 'float', description: 'القيمة الدائنة بالدولار', example: 0.00),
                            new OA\Property(property: 'debit_syp', type: 'number', format: 'float', description: 'القيمة المدينة بالليرة السورية', example: 0.00),
                            new OA\Property(property: 'credit_syp', type: 'number', format: 'float', description: 'القيمة الدائنة بالليرة السورية', example: 0.00),
                            new OA\Property(property: 'memo', type: 'string', description: 'شرح مخصص لهذا السطر المالي', example: 'فاتورة صيانة جهاز المشي رقم 3')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء قيد اليومية كمسودة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إنشاء سند القيد كمسودة'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccJournal')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ القيد المزدوج غير متوازن مالياً، أو المدخلات غير صحيحة')]
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

    #[OA\Get(
        path: '/accounting/journals/{id}',
        summary: '🔍 تفاصيل سند قيد معين',
        description: 'يسترجع تفاصيل سند القيد مع كافة الحركات المحاسبية المدرجة تحته والأطراف والفرع المرتبط به.',
        tags: ['Accounting - القيود اليومية العامة'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السند المحاسبي (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب بيانات السند بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب بيانات السند'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccJournal')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 السند المالي غير موجود')]
    public function show($id)
    {
        try {
            $journal = AccJournal::with('entries.account', 'period', 'safe', 'counterparty')->findOrFail($id);
            return $this->successResponse(new AccJournalResource($journal), 'تم جلب بيانات السند');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    #[OA\Post(
        path: '/accounting/journals/{id}/post',
        summary: '🔒 ترحيل قيد مالي مؤجل وتثبيته',
        description: 'يغير حالة السند المالي المحدد من (مسودة draft) إلى (مرحل posted). بعد الترحيل، يتم إثبات التأثير المالي وتحديث أرصدة الحسابات والصناديق تلقائياً، ويُمنع تعديل أو حذف القيد لاحقاً لسلامة التدقيق والقيود المالية.',
        tags: ['Accounting - القيود اليومية العامة'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السند المحاسبي (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم ترحيل السند وتأثيره المالي بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم ترحيل السند بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccJournal')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ السند تم ترحيله مسبقاً أو أن الفترة المالية الخاصة به مغلقة')]
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

    #[OA\Post(
        path: '/accounting/journals/{id}/reverse',
        summary: '↩️ عكس وإلغاء قيد مالي مرحل',
        description: 'في النظام المحاسبي للقيود المزدوجة، لا يمكن حذف القيود المرحّلة إطلاقاً. بدلاً من ذلك، يقوم هذا الإجراء بإنشاء **قيد عكسي تلقائي (Reversal Entry)** يقوم بعكس القيم (يجعل المدين دائناً والدائن مديناً) لإلغاء تأثير السند الأصلي بالكامل، مع تسجيل سبب الإلغاء.',
        tags: ['Accounting - القيود اليومية العامة'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السند المراد إلغاؤه (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['reason'],
            properties: [
                new OA\Property(property: 'reason', type: 'string', description: 'سبب إلغاء وعكس السند المحاسبي', example: 'خطأ في تقدير قيمة المصروف وتم التعديل')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم إلغاء السند وتوليد القيد العكسي بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم عكس السند بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccJournal')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ السند ملغي مسبقاً، أو غير مرحل ليتم إلغاؤه')]
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

    #[OA\Post(
        path: '/accounting/journals/{id}/cancel',
        summary: '❌ إلغاء سند قيد مالي',
        description: 'يقوم بإلغاء سند القيد المالي المحدد (سواء كان مسودة أو مرحلاً عبر قيد عكسي).',
        tags: ['Accounting - القيود اليومية العامة'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السند المراد إلغاؤه (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'cancellation_reason', type: 'string', description: 'سبب إلغاء السند المحاسبي', example: 'تم إلغاء السند لوجود خطأ في الإدخال'),
                new OA\Property(property: 'reason', type: 'string', description: 'سبب بديل للإلغاء', example: 'تم إلغاء السند')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم إلغاء السند بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إلغاء السند بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccJournal')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ خطأ أثناء محاولة إلغاء السند')]
    public function cancel(Request $request, $id)
    {
        try {
            $journal = AccJournal::with('entries')->findOrFail($id);
            $reason  = $request->input('cancellation_reason') ?? $request->input('reason') ?? 'إلغاء السند';
            $result  = $this->ledgerService->cancelJournal($journal, $reason);
            return $this->successResponse(new AccJournalResource($result), 'تم إلغاء السند بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
