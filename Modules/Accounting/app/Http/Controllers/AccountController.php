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
use OpenApi\Attributes as OA;

class AccountController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected LedgerService $ledgerService) {}

    #[OA\Get(
        path: '/accounting/accounts',
        summary: '🗂️ جلب دليل الحسابات المالي',
        description: 'يسترجع هذا الإجراء شجرة أو دليل الحسابات المالي بالكامل للفرع الحالي، مع إمكانية الفلترة حسب نوع الحساب أو حالة النشاط. السلوك الافتراضي هو جلب الحسابات الرئيسية (التي ليس لها أب) لعرض الشجرة بشكل متدرج. مفيد جداً لبناء شجرة الحسابات في واجهات النظام المالية.',
        tags: ['Accounting - دليل الحسابات العام'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'type', in: 'query', required: false, description: 'نوع الحساب (asset, liability, equity, revenue, expense)', schema: new OA\Schema(type: 'string', enum: ['asset', 'liability', 'equity', 'revenue', 'expense']))]
    #[OA\Parameter(name: 'is_active', in: 'query', required: false, description: 'تصفية حسب حالة النشاط (true = نشط فقط، false = معطل فقط)', schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'parent_id', in: 'query', required: false, description: 'معرف الحساب الأب لجلب الأبناء المباشرين له. أرسل "all" لجلب الجميع دون تدرج.', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب دليل الحسابات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب دليل الحسابات'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AccAccount'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح - الرجاء تسجيل الدخول أولاً')]
    #[OA\Response(response: 500, description: '🔥 خطأ داخلي في الخادم أثناء المعالجة')]
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

    #[OA\Post(
        path: '/accounting/accounts',
        summary: '➕ إضافة حساب جديد للدليل',
        description: 'ينشئ هذا الإجراء حساباً مالياً جديداً في شجرة الحسابات (دليل الحسابات). يمكن إضافته كحساب رئيسي أو كحساب فرعي تحت حساب أب محدد. السيناريو: إضافة حساب لمصروف جديد مثل (مصاريف تسويق) تحت حساب الأب (المصاريف التشغيلية).',
        tags: ['Accounting - دليل الحسابات العام'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'البيانات المطلوبة لإنشاء حساب جديد في دليل الحسابات المالي',
        content: new OA\JsonContent(
            required: ['code', 'name', 'type'],
            properties: [
                new OA\Property(property: 'code', type: 'string', description: 'رمز الحساب المحاسبي الفريد (مثال: 1101)', example: '1103'),
                new OA\Property(property: 'name', type: 'string', description: 'الاسم العربي للحساب المالي', example: 'صندوق الاستقبال - بطاقات'),
                new OA\Property(property: 'name_en', type: 'string', description: 'الاسم الإنجليزي للحساب المالي', example: 'Receiving Cash - Card'),
                new OA\Property(property: 'type', type: 'string', description: 'نوع الحساب المحاسبي الرئيسي', enum: ['asset', 'liability', 'equity', 'revenue', 'expense'], example: 'asset'),
                new OA\Property(property: 'currency', type: 'string', description: 'العملة المعامل بها الحساب (USD, SYP, or BOTH)', enum: ['USD', 'SYP', 'BOTH'], example: 'BOTH'),
                new OA\Property(property: 'parent_id', type: 'integer', description: 'معرف الحساب الأب في حال كان الحساب فرعياً', nullable: true, example: 1),
                new OA\Property(property: 'allow_manual_entry', type: 'boolean', description: 'هل يُسمح بإدخال قيود محاسبية يدوية على هذا الحساب', example: true),
                new OA\Property(property: 'is_active', type: 'boolean', description: 'حالة تفعيل الحساب', example: true)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الحساب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إضافة الحساب بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccAccount')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ مدخلات خاطئة أو رمز الحساب مكرر')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function store(StoreAccountRequest $request)
    {
        try {
            $account = AccAccount::create($request->validated());
            return $this->successResponse(new AccAccountResource($account), 'تم إضافة الحساب بنجاح', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: '/accounting/accounts/{id}',
        summary: '🔍 تفاصيل حساب مالي معين',
        description: 'يسترجع هذا الإجراء البيانات التفصيلية لحساب مالي معين عبر معرفه الفريد (ID) مع إدراج الحسابات الأبناء التابعة له والحساب الأب المباشر.',
        tags: ['Accounting - دليل الحسابات العام'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الحساب المحاسبي (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تفاصيل الحساب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب بيانات الحساب'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccAccount')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الحساب المالي المحدد غير موجود')]
    public function show($id)
    {
        try {
            $account = AccAccount::with('children', 'parent')->findOrFail($id);
            return $this->successResponse(new AccAccountResource($account), 'تم جلب بيانات الحساب');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    #[OA\Put(
        path: '/accounting/accounts/{id}',
        summary: '📝 تحديث حساب محاسبي',
        description: 'يسمح بتحديث البيانات الوصفية للحساب المحاسبي مثل الاسم، العملة، وحالة النشاط. السيناريو: إيقاف وتجميد حساب مصروف قديم عبر تحويل `is_active` إلى `false`.',
        tags: ['Accounting - دليل الحسابات العام'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الحساب المحاسبي (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'صندوق الاستقبال المعدل'),
                new OA\Property(property: 'name_en', type: 'string', example: 'Reception Cash Updated'),
                new OA\Property(property: 'currency', type: 'string', enum: ['USD', 'SYP', 'BOTH'], example: 'USD'),
                new OA\Property(property: 'allow_manual_entry', type: 'boolean', example: false),
                new OA\Property(property: 'is_active', type: 'boolean', example: true)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث بيانات الحساب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم تحديث الحساب بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccAccount')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الحساب غير موجود')]
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

    #[OA\Get(
        path: '/accounting/accounts/{id}/ledger',
        summary: '📊 كشف حساب تفصيلي (دفتر أستاذ مساعد)',
        description: 'يستخرج هذا الإجراء كشف حساب مالي تفصيلي (دفتر أستاذ مساعد) لحساب محدد خلال فترة زمنية معينة. يوضح الكشف الرصيد الافتتاحي، كافة الحركات المدينة والدائنة المدرجة، وتغير الرصيد التراكمي ونهاية الكشف بالرصيد الختامي.',
        tags: ['Accounting - دليل الحسابات العام'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الحساب المحاسبي (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'from', in: 'query', required: false, description: 'تاريخ بدء الكشف (تنسيق YYYY-MM-DD) - الافتراضي بداية الشهر الحالي', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to', in: 'query', required: false, description: 'تاريخ نهاية الكشف (تنسيق YYYY-MM-DD) - الافتراضي تاريخ اليوم', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استخراج كشف الحساب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب كشف الحساب'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'opening_balance_usd', type: 'number', format: 'float', example: 1200.50),
                        new OA\Property(property: 'opening_balance_syp', type: 'number', format: 'float', example: 500000.00),
                        new OA\Property(property: 'closing_balance_usd', type: 'number', format: 'float', example: 1750.00),
                        new OA\Property(property: 'closing_balance_syp', type: 'number', format: 'float', example: 350000.00),
                        new OA\Property(
                            property: 'entries',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 25),
                                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-06-15'),
                                    new OA\Property(property: 'reference_number', type: 'string', example: 'JV-00021'),
                                    new OA\Property(property: 'description', type: 'string', example: 'إيراد مبيعات اشتراك'),
                                    new OA\Property(property: 'debit_usd', type: 'number', format: 'float', example: 150.00),
                                    new OA\Property(property: 'credit_usd', type: 'number', format: 'float', example: 0.00),
                                    new OA\Property(property: 'debit_syp', type: 'number', format: 'float', example: 0.00),
                                    new OA\Property(property: 'credit_syp', type: 'number', format: 'float', example: 150000.00),
                                    new OA\Property(property: 'memo', type: 'string', example: 'دفعة العضو رقم 15')
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: '🔥 خطأ في استخراج الكشف')]
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
