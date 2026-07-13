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
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الفروع بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branches retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'فرع العليا')
                ]))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index()
    {
        $branches = $this->branchService->getAllBranches();
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
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'total_branches', type: 'integer'),
                    new OA\Property(property: 'active_branches', type: 'integer'),
                    new OA\Property(property: 'male_branches', type: 'integer'),
                    new OA\Property(property: 'female_branches', type: 'integer'),
                    new OA\Property(property: 'mixed_branches', type: 'integer'),
                ])
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
            ref: '#/components/schemas/StoreBranchRequest'
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الفرع بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch created successfully'),
                new OA\Property(property: 'data', type: 'object')
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
                new OA\Property(property: 'data', type: 'object')
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
            ref: '#/components/schemas/UpdateBranchRequest'
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الفرع بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch updated successfully'),
                new OA\Property(property: 'data', type: 'object')
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
        summary: '🗑️ حذف الفرع',
        description: 'إزالة الفرع المحدد من النظام.',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الفرع بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الفرع', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Branch not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    #[OA\Response(response: 409, description: '⚠️ تعارض - لا يمكن الحذف لارتباط السجل بسجلات أخرى', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'لا يمكن حذف السجل لوجود سجلات أخرى مرتبطة به.')]))]
    public function destroy($id)
    {
        $this->branchService->deleteBranch($id);
        return $this->successResponse(null, __('Branch deleted successfully'));
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
                new OA\Property(property: 'data', type: 'object')
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
