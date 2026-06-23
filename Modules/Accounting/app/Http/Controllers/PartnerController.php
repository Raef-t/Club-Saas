<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StorePartnerRequest;
use Modules\Accounting\Http\Requests\UpdatePartnerRequest;
use Modules\Accounting\Http\Resources\AccPartnerResource;
use Modules\Accounting\Models\AccPartner;
use Modules\Accounting\Services\ReportService;
use Modules\Shared\Traits\SuccessResponseTrait;

use OpenApi\Attributes as OA;

class PartnerController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected ReportService $reportService) {}

    #[OA\Get(
        path: '/accounting/partners',
        summary: '🗂️ عرض الشركاء المساهمين',
        description: 'يسترجع قائمة بكافة الشركاء المساهمين في رأسمال النادي الرياضي، مع تفاصيل حسابات رأس المال وحسابات المسحوبات الجارية ونسب الأرباح الخاصة بهم.',
        tags: ['Accounting - الشركاء وجاري الشركاء'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب قائمة الشركاء بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب الشركاء'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AccPartner'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function index()
    {
        try {
            $partners = AccPartner::with('capitalAccount', 'drawingsAccount')->orderBy('name')->get();
            return $this->successResponse(AccPartnerResource::collection($partners), 'تم جلب الشركاء');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: '/accounting/partners',
        summary: '➕ إضافة شريك جديد للمشروع',
        description: 'ينشئ هذا الإجراء شريكاً مساهماً جديداً في النظام، ويربطه بحساب رأس المال المخصص له في دليل الحسابات ضمن حقوق الملكية مع تحديد نسبة توزيع الأرباح وتاريخ الانضمام الفعلي.',
        tags: ['Accounting - الشركاء وجاري الشركاء'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات إنشاء الشريك الجديد',
        content: new OA\JsonContent(
            required: ['name', 'capital_account_id', 'profit_share_pct', 'joined_at'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'الاسم الكامل للشريك الجديد', example: 'خالد بن عبد الله التويجري'),
                new OA\Property(property: 'capital_account_id', type: 'integer', description: 'معرف الحساب المحاسبي لرأس مال الشريك', example: 12),
                new OA\Property(property: 'drawings_account_id', type: 'integer', description: 'معرف حساب جاري المسحوبات للشركاء إن وجد', nullable: true, example: 13),
                new OA\Property(property: 'profit_share_pct', type: 'number', format: 'float', description: 'نسبة الشريك من الأرباح والخسائر (0 - 100%)', example: 25.00),
                new OA\Property(property: 'joined_at', type: 'string', format: 'date', description: 'تاريخ انضمام الشريك (YYYY-MM-DD)', example: '2026-06-01'),
                new OA\Property(property: 'is_active', type: 'boolean', description: 'حالة النشاط للشراكة', example: true),
                new OA\Property(property: 'notes', type: 'string', description: 'شروط أو ملاحظات إضافية حول الشراكة', nullable: true, example: 'شريك ممول غير مشارك في الإدارة')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إضافة الشريك بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إضافة الشريك بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccPartner')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ الحسابات المحددة غير صالحة أو مكررة')]
    public function store(StorePartnerRequest $request)
    {
        try {
            $partner = AccPartner::create($request->validated());
            return $this->successResponse(new AccPartnerResource($partner->load('capitalAccount')), 'تم إضافة الشريك بنجاح', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: '/accounting/partners/{id}',
        summary: '🔍 تفاصيل شريك مساهم محدد',
        description: 'يسترجع تفاصيل شريك معين بواسطة معرفه الفريد (ID)، مع توضيح نسبة أرباحه وحسابه الجاري وحساب رأس المال الخاص به وحالته.',
        tags: ['Accounting - الشركاء وجاري الشركاء'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الشريك', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب بيانات الشريك بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب بيانات الشريك'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccPartner')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الشريك غير موجود')]
    public function show($id)
    {
        try {
            $partner = AccPartner::with('capitalAccount', 'drawingsAccount')->findOrFail($id);
            return $this->successResponse(new AccPartnerResource($partner), 'تم جلب بيانات الشريك');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    #[OA\Put(
        path: '/accounting/partners/{id}',
        summary: '📝 تحديث بيانات شريك مساهم',
        description: 'يسمح بتعديل البيانات الخاصة بالشريك المساهم مثل الاسم، نسبة توزيع الأرباح، حساب جاري المسحوبات، وحالة النشاط لتغيير الشروط التعاقدية أو إيقاف الشريك.',
        tags: ['Accounting - الشركاء وجاري الشركاء'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الشريك المساهم', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'الاسم الجديد للشريك المساهم', example: 'خالد بن عبد الله التويجري'),
                new OA\Property(property: 'capital_account_id', type: 'integer', description: 'معرف الحساب المحاسبي الجديد لرأس المال', example: 12),
                new OA\Property(property: 'drawings_account_id', type: 'integer', description: 'معرف الحساب المحاسبي للمسحوبات', nullable: true, example: 13),
                new OA\Property(property: 'profit_share_pct', type: 'number', format: 'float', description: 'النسبة المئوية المعدلة من الأرباح والخسائر', example: 30.00),
                new OA\Property(property: 'joined_at', type: 'string', format: 'date', description: 'تاريخ انضمام معدل', example: '2026-06-01'),
                new OA\Property(property: 'is_active', type: 'boolean', description: 'حالة النشاط للشراكة', example: true),
                new OA\Property(property: 'notes', type: 'string', description: 'شروط أو ملاحظات إضافية حول التحديث', nullable: true, example: 'تم زيادة النسبة بناءً على ملحق العقد رقم 2')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث بيانات الشريك بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم تحديث بيانات الشريك'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccPartner')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الشريك غير موجود')]
    public function update(UpdatePartnerRequest $request, $id)
    {
        try {
            $partner = AccPartner::findOrFail($id);
            $partner->update($request->validated());
            return $this->successResponse(new AccPartnerResource($partner->load('capitalAccount')), 'تم تحديث بيانات الشريك');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: '/accounting/partners/{id}/statement',
        summary: '📑 كشف الحساب المالي الجاري للشريك',
        description: 'يولد هذا الإجراء كشف حساب مالي جاري للشريك يوضح كافة مسحوباته الشخصية وحركاته المالية المودعة أو المسحوبة وتوزيع الأرباح الختامي للفترة المالية المحددة. مفيد للمطابقة المالية والمسائل الضريبية والقانونية بين الشركاء.',
        tags: ['Accounting - الشركاء وجاري الشركاء'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الشريك المساهم', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'period_id', in: 'query', required: true, description: 'معرف الفترة المالية المراد جلب كشف الجاري ضمن تواريخها', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب كشف حساب الشريك المساهم بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب كشف حساب الشريك'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'partner', type: 'object', description: 'معلومات الشريك العامة'),
                        new OA\Property(property: 'capital_balance', type: 'number', description: 'رصيد رأس المال الحالي للشريك'),
                        new OA\Property(property: 'drawings_balance', type: 'number', description: 'إجمالي المسحوبات الشخصية المسجلة خلال الفترة'),
                        new OA\Property(property: 'net_balance', type: 'number', description: 'الرصيد المالي الجاري الصافي للعمليات')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ معرف الفترة المحاسبية (period_id) مطلوب')]
    #[OA\Response(response: 404, description: '🚫 الشريك أو الفترة غير موجودين')]
    public function statement(Request $request, $id)
    {
        try {
            $periodId = $request->get('period_id');
            if (!$periodId) return $this->error('معرف الفترة مطلوب', 422);
            $data = $this->reportService->getPartnerStatement((int) $id, (int) $periodId);
            return $this->successResponse($data, 'تم جلب كشف حساب الشريك');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
