<?php

namespace Modules\Core\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Resources\AuditLogResource;
use Modules\Core\Services\AuditService;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Audit Logs',
    description: 'إدارة وسجلات التدقيق والأحداث في النظام والعمليات التي تمت على البيانات'
)]
class AuditLogController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    #[OA\Get(
        path: '/v1/audits',
        summary: '📋 جلب قائمة سجلات التدقيق مع الفلاتر المتقدمة',
        description: 'يتيح لك البحث والفلترة الدقيقة في كافة عمليات النظام (إنشاء، تعديل، حذف) مع معرفة المستخدم والفرع والتغييرات',
        operationId: 'getAuditLogsList',
        tags: ['Audit Logs'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        description: 'تصفية حسب معرف الفرع',
        required: false,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'user_id',
        in: 'query',
        description: 'تصفية حسب معرف المستخدم الذي قام بالعملية',
        required: false,
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\Parameter(
        name: 'event',
        in: 'query',
        description: 'نوع الإجراء (created, updated, deleted)',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['created', 'updated', 'deleted'], example: 'updated')
    )]
    #[OA\Parameter(
        name: 'module',
        in: 'query',
        description: 'اسم الموديول المتأثر (member, subscription, invoice, staff, branch, user)',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'member')
    )]
    #[OA\Parameter(
        name: 'subject_id',
        in: 'query',
        description: 'معرف العنصر المتأثر (مثلاً رقم المشترك أو رقم الفاتورة)',
        required: false,
        schema: new OA\Schema(type: 'integer', example: 204)
    )]
    #[OA\Parameter(
        name: 'date_from',
        in: 'query',
        description: 'تاريخ بداية الفترة (Y-m-d)',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-08-01')
    )]
    #[OA\Parameter(
        name: 'date_to',
        in: 'query',
        description: 'تاريخ نهاية الفترة (Y-m-d)',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-08-04')
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'بحث نصي في الوصف أو التغييرات',
        required: false,
        schema: new OA\Schema(type: 'string', example: '0501112233')
    )]
    #[OA\Parameter(
        name: 'sort_by',
        in: 'query',
        description: 'حقل الترتيب (created_at, id, event)',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'created_at')
    )]
    #[OA\Parameter(
        name: 'sort_order',
        in: 'query',
        description: 'اتجاه الترتيب (asc, desc)',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'desc')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'عدد النتائج في الصفحة',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 15, example: 15)
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'رقم الصفحة',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 1, example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب السجلات بنجاح'
    )]
    #[OA\Response(
        response: 401,
        description: '🔒 غير مصرح (Unauthenticated)'
    )]
    public function index(Request $request)
    {
        $filters = $request->only([
            'branch_id', 'user_id', 'event', 'module', 
            'subject_id', 'date_from', 'date_to', 
            'search', 'log_name', 'sort_by', 'sort_order'
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $logs = $this->auditService->getPaginatedLogs($filters, $perPage);

        return AuditLogResource::collection($logs);
    }

    #[OA\Get(
        path: '/v1/audits/meta',
        summary: '⚙️ جلب قائمة الموديولات والأحداث المتاحة لفلترة الواجهة',
        operationId: 'getAuditLogMetadata',
        tags: ['Audit Logs'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب الفلاتر المتاحة بنجاح'
    )]
    public function meta(): JsonResponse
    {
        $meta = $this->auditService->getFilterMetadata();
        return response()->json([
            'status' => 'success',
            'data'   => $meta,
        ]);
    }

    #[OA\Get(
        path: '/v1/audits/{id}',
        summary: '🔍 عرض تفاصيل سجل تدقيق محدد',
        operationId: 'getAuditLogDetail',
        tags: ['Audit Logs'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'معرف سجل التدقيق',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1042)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب تفاصيل السجل بنجاح'
    )]
    #[OA\Response(
        response: 404,
        description: '❌ السجل غير موجود'
    )]
    public function show($id)
    {
        $log = Activity::with(['causer.person', 'user.person', 'subject'])->findOrFail($id);
        return new AuditLogResource($log);
    }
}
