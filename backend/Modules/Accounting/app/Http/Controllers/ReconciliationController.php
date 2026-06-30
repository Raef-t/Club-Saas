<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Http\Requests\StoreReconciliationRequest;
use Modules\Accounting\Models\AccReconciliation;
use Modules\Accounting\Models\AccSafe;
use Modules\Accounting\Services\LedgerService;
use Modules\Shared\Traits\SuccessResponseTrait;

use OpenApi\Attributes as OA;

class ReconciliationController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected LedgerService $ledgerService) {}

    #[OA\Get(
        path: '/accounting/reconciliations',
        summary: '🗂️ سجل تسويات ومطابقات الصناديق',
        description: 'يسترجع هذا الإجراء قائمة بكافة سجلات تسويات الصناديق والخزائن المالية المدخلة يدوياً بواسطة موظفي الجرد أو المدراء الماليين مع تفاصيل الأرصدة المطابقة والتاريخ والملاحظات.',
        tags: ['Accounting - تسويات ومطابقة الصناديق'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب سجلات التسوية بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب سجلات التسوية'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AccReconciliation'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
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

    #[OA\Post(
        path: '/accounting/reconciliations',
        summary: '➕ تسجيل مطابقة وجرد فعلي لصندوق مالي',
        description: 'يقوم هذا الإجراء بتسجيل عملية تسوية لصندوق مالي معين. يقوم النظام تلقائياً بقراءة الرصيد الدفتري الحالي للصندوق من الحساب المحاسبي المرتبط به ويقارنه مع المدخلات الفعلية المعبأة بالطلب (الرصيد الفعلي بالدولار والليرة) لإنشاء سجل تسوية يوضح مقدار العجز أو الفائض.',
        tags: ['Accounting - تسويات ومطابقة الصناديق'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات الجرد الفعلي المسحوبة من الكاش في الصندوق يدوياً',
        content: new OA\JsonContent(
            required: ['safe_id', 'period_id', 'physical_balance_usd', 'physical_balance_syp'],
            properties: [
                new OA\Property(property: 'safe_id', type: 'integer', description: 'معرف الصندوق المالي المراد مطابقته', example: 2),
                new OA\Property(property: 'period_id', type: 'integer', description: 'معرف الفترة المالية الجارية المطابق فيها', example: 1),
                new OA\Property(property: 'physical_balance_usd', type: 'number', format: 'float', description: 'المبلغ الفعلي المقاس بالدولار يدوياً في الخزينة', example: 1200.00),
                new OA\Property(property: 'physical_balance_syp', type: 'number', format: 'float', description: 'المبلغ الفعلي المقاس بالليرة السورية يدوياً في الخزينة', example: 15000000.00),
                new OA\Property(property: 'notes', type: 'string', description: 'أي ملاحظات أو تبريرات لظهور فروقات كالعجز أو الفائض', nullable: true, example: 'فروقات طفيفة بسبب كسر الفكة')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم تسجيل عملية التسوية والمطابقة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم حفظ التسوية بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccReconciliation')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ الصندوق أو الفترة غير نشطين أو غير موجودين')]
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

    #[OA\Get(
        path: '/accounting/reconciliations/{id}',
        summary: '🔍 تفاصيل تسوية صندوق مالي معينة',
        description: 'يسترجع البيانات التفصيلية لعملية تسوية ومطابقة صندوق معينة، بما في ذلك الأرصدة الدفترية والفعلية المقاسة والفرق والتاريخ وتوقيع الشخص المسؤول عن المطابقة.',
        tags: ['Accounting - تسويات ومطابقة الصناديق'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف عملية التسوية (ID)', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب تفاصيل التسوية بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب بيانات التسوية'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccReconciliation')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 سجل التسوية المحدد غير موجود')]
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
