<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Http\Requests\StorePeriodRequest;
use Modules\Accounting\Http\Resources\AccPeriodResource;
use Modules\Accounting\Models\AccPeriod;
use Modules\Accounting\Services\PeriodService;
use Modules\Shared\Traits\SuccessResponseTrait;

use OpenApi\Attributes as OA;

class PeriodController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected PeriodService $periodService) {}

    #[OA\Get(
        path: '/accounting/periods',
        summary: '🗂️ عرض الفترات المحاسبية',
        description: 'يسترجع هذا الإجراء قائمة بكافة الفترات المالية والمحاسبية المعرفة في النظام (مثل الأشهر المالية) مرتبة تنازلياً حسب تاريخ البداية. يُستخدم لعرض الفترات في لوحة التحكم وتحديد الفترة الحالية النشطة.',
        tags: ['Accounting - الفترات المحاسبية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب الفترات المحاسبية بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب الفترات المحاسبية بنجاح'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AccPeriod'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح - يجب تسجيل الدخول')]
    #[OA\Response(response: 500, description: '🔥 خطأ داخلي أثناء جلب البيانات')]
    public function index()
    {
        try {
            $periods = AccPeriod::orderBy('start_date', 'desc')->get();
            return $this->successResponse(AccPeriodResource::collection($periods), 'تم جلب الفترات المحاسبية بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: '/accounting/periods',
        summary: '➕ إنشاء فترة محاسبية جديدة',
        description: 'ينشئ فترة مالية جديدة (مثال: شهر جديد) في النظام لتمكين المحاسبين من ترحيل القيود اليومية وعمليات الصناديق ضمنها. تفرض العملية شروط عدم تداخل تواريخ الفترة الجديدة مع أي فترات محاسبية جارية أخرى لمنع خلط الحسابات.',
        tags: ['Accounting - الفترات المحاسبية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات إنشاء الفترة المالية الجديدة',
        content: new OA\JsonContent(
            required: ['name', 'start_date', 'end_date'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'اسم الفترة المالية الفريد والمميز', example: 'شهر تموز 2026'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'تاريخ بداية الفترة (YYYY-MM-DD)', example: '2026-07-01'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', description: 'تاريخ نهاية الفترة (يجب أن يكون بعد تاريخ البداية)', example: '2026-07-31'),
                new OA\Property(property: 'notes', type: 'string', description: 'ملاحظات إضافية بخصوص الفترة المحاسبية الجديدة', nullable: true, example: 'فترة الربع الثالث')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الفترة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إنشاء الفترة المحاسبية بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccPeriod')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ مدخلات خاطئة أو تداخل في التواريخ')]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من الحقول')]
    public function store(StorePeriodRequest $request)
    {
        try {
            $period = AccPeriod::create($request->validated());
            return $this->successResponse(new AccPeriodResource($period), 'تم إنشاء الفترة المحاسبية بنجاح', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: '/accounting/periods/{id}',
        summary: '🔍 تفاصيل فترة محاسبية محددة',
        description: 'يسترجع البيانات التفصيلية لفترة مالية معينة باستخدام معرفها الفريد (ID)، بما في ذلك تواريخها، وحالتها الحالية، ومن قام بإغلاقها وتاريخ الإغلاق إن وجد.',
        tags: ['Accounting - الفترات المحاسبية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفترة المالية', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تفاصيل الفترة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب بيانات الفترة'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccPeriod')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الفترة المالية غير موجودة')]
    public function show($id)
    {
        try {
            $period = AccPeriod::findOrFail($id);
            return $this->successResponse(new AccPeriodResource($period), 'تم جلب بيانات الفترة');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    #[OA\Post(
        path: '/accounting/periods/{id}/close',
        summary: '🔒 إغلاق الفترة المحاسبية مؤقتاً',
        description: 'يغلق هذا الإجراء الفترة المالية مؤقتاً لمنع تعديل القيود أو الصناديق يدوياً، مع إتاحة إمكانية إعادة فتحها لاحقاً للمدراء فقط في حال الحاجة لإجراء تسويات ضرورية. يمثل سيناريو نهاية الشهر الفنية.',
        tags: ['Accounting - الفترات المحاسبية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفترة المالية المراد إغلاقها', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم إغلاق الفترة المحاسبية بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إغلاق الفترة المحاسبية بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccPeriod')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ لا يمكن إغلاق الفترة (قد تكون مغلقة أو مقفلة مسبقاً)')]
    public function close($id)
    {
        try {
            $period = AccPeriod::findOrFail($id);
            $period = $this->periodService->closePeriod($period, Auth::id());
            return $this->successResponse(new AccPeriodResource($period), 'تم إغلاق الفترة المحاسبية بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/accounting/periods/{id}/lock',
        summary: '🔏 قفل الفترة المحاسبية نهائياً',
        description: 'يقوم هذا الإجراء بقفل الفترة المالية نهائياً وحظر أي إمكانية لإعادة فتحها أو تعديل أي حركة مالية مرتبطة بها مجدداً. يُستخدم عادةً لإقفال الحسابات الختامية السنوية بعد الجرد وتوليد الميزانية العمومية الرسمية.',
        tags: ['Accounting - الفترات المحاسبية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفترة المالية المراد قفلها نهائياً', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم قفل الفترة المالية نهائياً وبشكل غير قابل للتراجع',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم قفل الفترة نهائياً'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccPeriod')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ لا يمكن قفل الفترة (قد لا تكون مغلقة أولاً أو مقفلة بالفعل)')]
    public function lock($id)
    {
        try {
            $period = AccPeriod::findOrFail($id);
            $period = $this->periodService->lockPeriod($period, Auth::id());
            return $this->successResponse(new AccPeriodResource($period), 'تم قفل الفترة نهائياً');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/accounting/periods/{id}/reopen',
        summary: '🔓 إعادة فتح فترة مغلقة مؤقتاً',
        description: 'يسمح هذا الإجراء للمدير المالي بإعادة فتح فترة مالية مغلقة مؤقتاً (Closed) لإجراء قيود تسوية طارئة أو تصحيح أخطاء القيود. لا يمكن إعادة فتح الفترات المقفلة نهائياً (Locked).',
        tags: ['Accounting - الفترات المحاسبية'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفترة المراد إعادة فتحها', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم إعادة فتح الفترة بنجاح وجعلها مسودة للقيود الجارية',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إعادة فتح الفترة بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccPeriod')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ لا يمكن إعادة فتح الفترة (إذا كانت مقفلة نهائياً أو مفتوحة بالفعل)')]
    public function reopen($id)
    {
        try {
            $period = AccPeriod::findOrFail($id);
            $period = $this->periodService->reopenPeriod($period);
            return $this->successResponse(new AccPeriodResource($period), 'تم إعادة فتح الفترة بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
