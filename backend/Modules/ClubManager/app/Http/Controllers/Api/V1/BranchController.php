<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\ClubManager\Http\Requests\StoreBranchRequest;
use Modules\ClubManager\Http\Requests\UpdateBranchRequest;
use Modules\ClubManager\Http\Resources\BranchResource;
use Modules\ClubManager\Services\BranchService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class BranchController extends BaseController
{
    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    #[OA\Get(
        path: '/v1/branches',
        summary: '🏢 عرض جميع الفروع',
        description: 'استرجاع قائمة بجميع الفروع المتاحة في النظام.',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'club_id', in: 'query', required: false, description: 'تصفية حسب النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الفروع بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branches retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'club_id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'فرع العليا الرئيسي'),
                            new OA\Property(property: 'code', type: 'string', nullable: true, example: 'BR-001'),
                            new OA\Property(property: 'phone', type: 'string', nullable: true, example: '0112345678'),
                            new OA\Property(property: 'email', type: 'string', nullable: true, example: 'olaya@club.com'),
                            new OA\Property(property: 'address', type: 'string', nullable: true, example: 'طريق الملك فهد، الرياض'),
                            new OA\Property(property: 'gender_type', type: 'string', example: 'mixed'),
                            new OA\Property(property: 'is_active', type: 'boolean', example: true),
                            new OA\Property(property: 'opening_time', type: 'string', nullable: true, example: '06:00'),
                            new OA\Property(property: 'closing_time', type: 'string', nullable: true, example: '23:00'),
                            new OA\Property(property: 'created_at', type: 'string', example: '2026-01-01T10:00:00.000000Z'),
                            new OA\Property(property: 'updated_at', type: 'string', example: '2026-01-01T10:00:00.000000Z')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request)
    {
        $branches = $this->branchService->getAllBranches($request->all());
        return $this->successResponse(BranchResource::collection($branches), __('Branches retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/branches/stats',
        summary: '📊 إحصائيات الفروع',
        description: 'استرجاع إحصائيات الفروع (العدد الكلي، النشطة، المخصصة للذكور/الإناث/المختلط).',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الإحصائيات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch statistics retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total_branches', type: 'integer', example: 5),
                        new OA\Property(property: 'active_branches', type: 'integer', example: 4),
                        new OA\Property(property: 'male_branches', type: 'integer', example: 2),
                        new OA\Property(property: 'female_branches', type: 'integer', example: 2),
                        new OA\Property(property: 'mixed_branches', type: 'integer', example: 1)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function stats()
    {
        $stats = $this->branchService->getStats();
        return $this->successResponse($stats, __('Branch statistics retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/branches',
        summary: '➕ إنشاء فرع جديد',
        description: 'إضافة فرع جديد إلى النظام الخاص بالنادي.',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['club_id', 'name'],
            properties: [
                new OA\Property(property: 'club_id', description: '(مطلوب) معرف النادي', type: 'integer', example: 1),
                new OA\Property(property: 'name', description: '(مطلوب) اسم الفرع', type: 'string', example: 'فرع الياسمين'),
                new OA\Property(property: 'code', description: '(اختياري) كود الفرع', type: 'string', nullable: true, example: 'BR-002'),
                new OA\Property(property: 'phone', description: '(اختياري) رقم الهاتف', type: 'string', nullable: true, example: '0119876543'),
                new OA\Property(property: 'email', description: '(اختياري) البريد الإلكتروني', type: 'string', nullable: true, example: 'yasmine@club.com'),
                new OA\Property(property: 'address', description: '(اختياري) العنوان', type: 'string', nullable: true, example: 'حي الياسمين، الرياض'),
                new OA\Property(property: 'gender_type', description: 'تخصيص الجنس (male, female, mixed)', type: 'string', enum: ['male', 'female', 'mixed'], example: 'male'),
                new OA\Property(property: 'is_active', description: 'حالة التفعيل', type: 'boolean', example: true),
                new OA\Property(property: 'opening_time', description: 'وقت الافتتاح (HH:mm)', type: 'string', format: 'time', example: '06:00'),
                new OA\Property(property: 'closing_time', description: 'وقت الإغلاق (HH:mm)', type: 'string', format: 'time', example: '23:00')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الفرع بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch created successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 2),
                        new OA\Property(property: 'club_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'فرع الياسمين'),
                        new OA\Property(property: 'code', type: 'string', example: 'BR-002'),
                        new OA\Property(property: 'gender_type', type: 'string', example: 'male'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', example: '2026-09-06T12:55:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreBranchRequest $request)
    {
        $branch = $this->branchService->createBranch($request->validated());
        return $this->successResponse(new BranchResource($branch), __('Branch created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/branches/{id}',
        summary: '🔍 تفاصيل الفرع',
        description: 'استرجاع تفاصيل فرع محدد بواسطة المعرف الخاص به.',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تفاصيل الفرع',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch details retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'club_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'فرع العليا الرئيسي'),
                        new OA\Property(property: 'code', type: 'string', example: 'BR-001'),
                        new OA\Property(property: 'phone', type: 'string', example: '0112345678'),
                        new OA\Property(property: 'email', type: 'string', example: 'olaya@club.com'),
                        new OA\Property(property: 'address', type: 'string', example: 'طريق الملك فهد، الرياض'),
                        new OA\Property(property: 'gender_type', type: 'string', example: 'mixed'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'opening_time', type: 'string', example: '06:00'),
                        new OA\Property(property: 'closing_time', type: 'string', example: '23:00')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الفرع', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Branch not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $branch = $this->branchService->getBranchById($id);
        return $this->successResponse(new BranchResource($branch), __('Branch details retrieved'));
    }

    #[OA\Put(
        path: '/v1/branches/{id}',
        summary: '📝 تحديث الفرع',
        description: 'تعديل البيانات الخاصة بفرع موجود مسبقاً في النظام.',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', description: 'اسم الفرع', type: 'string', example: 'فرع العليا المُحدث'),
                new OA\Property(property: 'code', description: 'كود الفرع', type: 'string', example: 'BR-001-A'),
                new OA\Property(property: 'phone', description: 'رقم الهاتف', type: 'string', example: '0112345699'),
                new OA\Property(property: 'email', description: 'البريد الإلكتروني', type: 'string', example: 'olaya.new@club.com'),
                new OA\Property(property: 'address', description: 'العنوان', type: 'string', example: 'طريق الملك فهد، الرياض'),
                new OA\Property(property: 'gender_type', description: 'الجنس', type: 'string', enum: ['male', 'female', 'mixed'], example: 'mixed'),
                new OA\Property(property: 'is_active', description: 'الحالة', type: 'boolean', example: true),
                new OA\Property(property: 'opening_time', description: 'وقت الافتتاح', type: 'string', example: '06:00'),
                new OA\Property(property: 'closing_time', description: 'وقت الإغلاق', type: 'string', example: '23:00')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الفرع بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch updated successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'فرع العليا المُحدث'),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2026-09-06T12:55:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الفرع', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Branch not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateBranchRequest $request, $id)
    {
        $branch = $this->branchService->updateBranch($id, $request->validated());
        return $this->successResponse(new BranchResource($branch), __('Branch updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/branches/{id}',
        summary: '🗑️ حذف الفرع (Soft Delete)',
        description: 'حذف الفرع بالكامل من النظام مع كافة المشتركين والمدربين والأنشطة والاشتراكات المتعلقة به. يتطلب إرسال كلمة التأكيد "delete".',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'confirmation', type: 'string', description: 'تأكيد الحذف (delete)', example: 'delete')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(
        response: 422, 
        description: '⚠️ خطأ عدم إرسال كلمة التأكيد delete',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'يرجى تأكيد الحذف بإرسال كلمة "delete" في حقل التأكيد (confirmation).')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الفرع', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Branch not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(Request $request, $id)
    {
        $confirmation = $request->input('confirmation', '');
        $this->branchService->deleteBranch((int) $id, (string) $confirmation);
        return $this->successResponse(null, __('Branch deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/branches/trashed',
        summary: '🗑️ عرض الفروع المحذوفة (سلة المهملات)',
        description: 'جلب قائمة بالفروع التي تم حذفها ناعماً.',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'club_id', in: 'query', required: false, description: 'تصفية حسب النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب الفروع المحذوفة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Trashed branches retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 3),
                            new OA\Property(property: 'name', type: 'string', example: 'فرع الملقا السابق'),
                            new OA\Property(property: 'deleted_at', type: 'string', example: '2026-08-01T10:00:00.000000Z')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function trashed(Request $request)
    {
        $branches = $this->branchService->getTrashed($request->all());
        return $this->successResponse(BranchResource::collection($branches), __('Trashed branches retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/branches/{id}/restore',
        summary: '♻️ استرجاع فرع محذوف',
        description: 'استرجاع الفرع وكافة العلاقات التابعة له من سلة المهملات.',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 3))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الفرع بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch restored successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 3),
                        new OA\Property(property: 'name', type: 'string', example: 'فرع الملقا السابق'),
                        new OA\Property(property: 'deleted_at', type: 'string', nullable: true, example: null)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الفرع غير موجود في سلة المهملات', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function restore($id)
    {
        $branch = $this->branchService->restoreBranch($id);
        return $this->successResponse(new BranchResource($branch), __('Branch restored successfully'));
    }

    #[OA\Patch(
        path: '/v1/branches/{id}/toggle-status',
        summary: '🔄 تفعيل / تعطيل الفرع',
        description: 'تغيير حالة الفرع من نشط إلى غير نشط والعكس.',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث حالة الفرع',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch status updated'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'فرع العليا الرئيسي'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: false)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الفرع', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Branch not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function toggleStatus($id)
    {
        $branch = $this->branchService->toggleStatus($id);
        return $this->successResponse(new BranchResource($branch), __('Branch status updated'));
    }
}
