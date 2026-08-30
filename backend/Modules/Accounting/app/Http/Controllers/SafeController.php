<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StoreSafeRequest;
use Modules\Accounting\Http\Requests\UpdateSafeRequest;
use Modules\Accounting\Http\Resources\AccSafeResource;
use Modules\Accounting\Models\AccSafe;
use Modules\Accounting\Services\ReportService;
use Modules\Shared\Traits\SuccessResponseTrait;
use OpenApi\Attributes as OA;

class SafeController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected ReportService $reportService) {}

    #[OA\Get(
        path: '/accounting/safes',
        summary: '🏦 جلب قائمة الصناديق والخزائن المتاحة',
        description: 'يسترجع هذا الإجراء قائمة بكافة الصناديق والخزائن النقدية والبنوك المعرفة بالنظام مع تفاصيل الأرصدة والحسابات المربوطة بها. يمكن استخدامها لعرض الخزائن المتوفرة لموظفي الاستقبال.',
        tags: ['Accounting - الصناديق والعهد المالية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'is_active', in: 'query', required: false, description: 'تصفية حسب حالة النشاط (true = نشط فقط، false = معطل فقط)', schema: new OA\Schema(type: 'boolean'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب قائمة الصناديق بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب الصناديق'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AccSafe'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function index(Request $request)
    {
        try {
            $branchId = $request->header('X-Branch-ID') ?: $request->input('branch_id');
            $safes = AccSafe::with(['account', 'branch'])
                ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->when($request->has('is_active'), fn($q) => $q->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)))
                ->orderBy('name')->get();
            return $this->successResponse(AccSafeResource::collection($safes), 'تم جلب الصناديق');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: '/accounting/safes',
        summary: '➕ إضافة صندوق مالي/خزينة جديدة للفرع',
        description: 'ينشئ هذا الإجراء صندوقاً مالياً جديداً (مثل صندوق الاستقبال، عهدة استقبال السيدات، خزينة الفرع الرئيسية) ويربطه بحساب محاسبي مقابل من دليل الحسابات ليعكس عملياته المالية.',
        tags: ['Accounting - الصناديق والعهد المالية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'البيانات المطلوبة لإنشاء صندوق أو خزينة جديدة',
        content: new OA\JsonContent(
            required: ['name', 'account_id', 'currency'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'اسم الصندوق المميز للفرع', example: 'خزينة الفرع الرئيسي - دولار'),
                new OA\Property(property: 'account_id', type: 'integer', description: 'معرف الحساب المالي المرتبط بالصندوق من دليل الحسابات (يجب أن يكون من الأصول المتداولة)', example: 3),
                new OA\Property(property: 'currency', type: 'string', description: 'العملة الأساسية للصندوق', enum: ['USD', 'SYP'], example: 'USD'),
                new OA\Property(property: 'responsible_user_id', type: 'integer', description: 'معرف المستخدم المسؤول عن عهدة هذا الصندوق', nullable: true, example: 2),
                new OA\Property(property: 'is_active', type: 'boolean', description: 'حالة نشاط الصندوق', example: true),
                new OA\Property(property: 'notes', type: 'string', description: 'ملاحظات إضافية حول الاستخدام أو العهدة', nullable: true, example: 'صندوق دولار مخصص للاشتراكات السنوية الفاخرة'),
                new OA\Property(property: 'branch_id', type: 'integer', description: 'معرف الفرع الجغرافي للنادي التابع له الصندوق', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الصندوق بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إضافة الصندوق بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccSafe')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ مدخلات غير صحيحة أو الحساب المحدد غير موجود')]
    public function store(StoreSafeRequest $request)
    {
        try {
            $safe = AccSafe::create($request->validated());
            return $this->successResponse(new AccSafeResource($safe->load('account')), 'تم إضافة الصندوق بنجاح', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: '/accounting/safes/{id}',
        summary: '🔍 تفاصيل الصندوق المالي',
        description: 'يسترجع تفاصيل الصندوق المالي المحدد مع الحساب المرتبط به وحالته الحالية.',
        tags: ['Accounting - الصناديق والعهد المالية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الصندوق (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب تفاصيل الصندوق بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب بيانات الصندوق'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccSafe')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الصندوق غير موجود')]
    public function show($id)
    {
        try {
            $safe = AccSafe::with('account')->findOrFail($id);
            return $this->successResponse(new AccSafeResource($safe), 'تم جلب بيانات الصندوق');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    #[OA\Put(
        path: '/accounting/safes/{id}',
        summary: '📝 تحديث بيانات الصندوق',
        description: 'يحدث البيانات التفصيلية للصندوق مثل الاسم، الملاحظات، أو تعيين مسؤول عهدة جديد.',
        tags: ['Accounting - الصناديق والعهد المالية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الصندوق المالي (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'صندوق الاستقبال الافتراضي المعدل'),
                new OA\Property(property: 'responsible_user_id', type: 'integer', example: 5),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'notes', type: 'string', example: 'تعديل مسؤول العهدة ليكون الموظف المناوب الجديد')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث بيانات الصندوق بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم تحديث الصندوق بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccSafe')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الصندوق غير موجود')]
    public function update(UpdateSafeRequest $request, $id)
    {
        try {
            $safe = AccSafe::findOrFail($id);
            $safe->update($request->validated());
            return $this->successResponse(new AccSafeResource($safe->load('account')), 'تم تحديث الصندوق بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: '/accounting/safes/{id}/statement',
        summary: '📊 كشف حركة الصندوق التفصيلي',
        description: 'يستخرج هذا الإجراء كشفاً بيانياً تفصيلياً بكافة الحركات النقدية (الداخلة والخارجة) التي تمت على صندوق محدد في نطاق تواريخ معين. يفيد في عمليات تسليم الوردية وإحصاء الرصيد الفعلي للصندوق ومطابقته.',
        tags: ['Accounting - الصناديق والعهد المالية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الصندوق المالي (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'from', in: 'query', required: false, description: 'تاريخ بدء الفلترة (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to', in: 'query', required: false, description: 'تاريخ نهاية الفلترة (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استخراج كشف حركة الصندوق بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب كشف الصندوق'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'safe_name', type: 'string', example: 'صندوق الاستقبال الرئيسي'),
                        new OA\Property(property: 'currency', type: 'string', example: 'USD'),
                        new OA\Property(property: 'opening_balance', type: 'number', format: 'float', example: 450.00),
                        new OA\Property(property: 'closing_balance', type: 'number', format: 'float', example: 850.00),
                        new OA\Property(property: 'total_debits', type: 'number', format: 'float', description: 'إجمالي المقبوضات/سندات القبض', example: 500.00),
                        new OA\Property(property: 'total_credits', type: 'number', format: 'float', description: 'إجمالي المدفوعات/سندات الصرف', example: 100.00),
                        new OA\Property(
                            property: 'transactions',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'journal_id', type: 'integer', example: 12),
                                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-06-18'),
                                    new OA\Property(property: 'reference_number', type: 'string', example: 'RV-0004'),
                                    new OA\Property(property: 'description', type: 'string', example: 'قيد تلقائي: إيراد اشتراك لاعب'),
                                    new OA\Property(property: 'type', type: 'string', example: 'RV'),
                                    new OA\Property(property: 'amount', type: 'number', format: 'float', description: 'المبلغ المحرك (موجب للمقبوضات، سالب للمدفوعات)', example: 120.00)
                                ]
                            )
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: '🔥 خطأ في السيرفر أثناء المعالجة')]
    public function statement(Request $request, $id)
    {
        try {
            $from = $request->get('from', $request->get('from_date', now()->startOfYear()->toDateString()));
            $to   = $request->get('to', $request->get('to_date', now()->toDateString()));
            $data = $this->reportService->getSafeStatement((int) $id, $from, $to);
            return $this->successResponse($data, 'تم جلب كشف الصندوق');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Delete(
        path: '/accounting/safes/{id}',
        summary: '🗑️ حذف صندوق مالي',
        description: 'يحذف الصندوق المالي بشرط عدم وجود أي حركات أو قيود مالية مسجلة عليه.',
        tags: ['Accounting - الصناديق والعهد المالية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الصندوق (ID)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ تم حذف الصندوق بنجاح')]
    #[OA\Response(response: 400, description: '❌ لا يمكن حذف الصندوق لوجود حركات مالية')]
    #[OA\Response(response: 404, description: '🚫 الصندوق غير موجود')]
    public function destroy($id)
    {
        try {
            $safe = AccSafe::findOrFail($id);

            // التحقق من وجود حركات أو قيود مالية مرتبطة بالصندوق
            $hasJournals  = \Illuminate\Support\Facades\DB::table('acc_journals')->where('safe_id', $safe->id)->exists();
            $hasPayments  = \Illuminate\Support\Facades\DB::table('payments')->where('safe_id', $safe->id)->exists();
            $hasSalaries  = \Illuminate\Support\Facades\DB::table('acc_salary_payments')->where('safe_id', $safe->id)->exists();
            $hasReconcile = \Illuminate\Support\Facades\DB::table('acc_reconciliations')->where('safe_id', $safe->id)->exists();

            if ($hasJournals || $hasPayments || $hasSalaries || $hasReconcile) {
                return $this->error('لا يمكن حذف الصندوق لوجود حركات أو قيود مالية مسجلة عليه. يمكنك تعطيله بدلاً من ذلك.', 400);
            }

            // التحقق من كونه صندوقاً افتراضياً لفرع
            $isDefault = \Illuminate\Support\Facades\DB::table('acc_branch_settings')->where('default_safe_id', $safe->id)->exists();
            if ($isDefault) {
                return $this->error('لا يمكن حذف الصندوق لأنه محدد كصندوق افتراضي للفرع في الإعدادات.', 400);
            }

            $safe->delete();

            return $this->successResponse(null, 'تم حذف الصندوق بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
