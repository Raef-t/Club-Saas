<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Modules\ClubManager\Models\BranchShift;
use Modules\ClubManager\Http\Requests\StoreBranchShiftRequest;
use Modules\ClubManager\Http\Requests\UpdateBranchShiftRequest;
use OpenApi\Attributes as OA;

class BranchShiftController extends BaseController
{
    #[OA\Get(
        path: '/v1/branches/{branch}/shifts',
        summary: '🕒 عرض ورديات الفرع',
        description: 'استرجاع جميع الورديات (Shifts) المسجلة لفرع معين.',
        tags: ['Branch Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الورديات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch shifts retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Morning Shift'),
                    new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                    new OA\Property(property: 'start_time', type: 'string', example: '08:00'),
                    new OA\Property(property: 'end_time', type: 'string', example: '16:00'),
                    new OA\Property(property: 'gender_allowed', type: 'string', example: 'mixed')
                ]))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request, $branchId)
    {
        $query = BranchShift::where('branch_id', $branchId);

        if ($request->input('per_page') === 'all' || $request->boolean('all') || $request->input('paginate') === 'false') {
            $shifts = $query->get();
        } else {
            $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
            $shifts = $query->paginate($perPage);
        }

        return $this->successResponse($shifts, __('Branch shifts retrieved'));
    }

    #[OA\Post(
        path: '/v1/branches/{branch}/shifts',
        summary: '➕ إضافة وردية جديدة',
        description: 'إضافة وردية عمل جديدة لفرع محدد.',
        tags: ['Branch Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'start_time', 'end_time', 'gender_allowed'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'اسم الوردية', example: 'وردية الصباح'),
                new OA\Property(property: 'start_time', type: 'string', format: 'time', example: '16:00'),
                new OA\Property(property: 'end_time', type: 'string', format: 'time', example: '23:59'),
                new OA\Property(property: 'gender_allowed', type: 'string', description: 'male, female, mixed', example: 'mixed')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الوردية بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch shift created'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreBranchShiftRequest $request, $branchId)
    {
        $validated = $request->validated();
        $validated['branch_id'] = $branchId;

        // Create shift
        $shift = BranchShift::create($validated);

        return $this->successResponse($shift, __('Branch shift created'), 201);
    }

    #[OA\Delete(
        path: '/v1/branches/{branch}/shifts/{shift}',
        summary: '🗑️ حذف الوردية (Soft Delete)',
        description: 'إزالة وردية محددة من الفرع. يتطلب إرسال كلمة التأكيد "delete" اختيارياً.',
        tags: ['Branch Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'shift', in: 'path', required: true, description: 'معرف الوردية', schema: new OA\Schema(type: 'integer', example: 10))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'confirmation', type: 'string', description: 'تأكيد الحذف (delete)', example: '')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم الحذف بنجاح')]
    #[OA\Response(response: 422, description: '⚠️ خطأ عدم إرسال كلمة التأكيد "delete"')]
    public function destroy(Request $request, $branchId, $id)
    {
        $confirm = strtolower(trim($request->input('confirm') ?? $request->input('confirmation') ?? $request->input('confirm_text') ?? ''));

        if ($confirm !== 'delete') {
            return $this->errorResponse(
                __('سيتم حذف هذه الوردية، هل أنت متأكد؟ أرسل "delete" للتأكيد.'),
                422
            );
        }

        $shift = BranchShift::where('branch_id', $branchId)->findOrFail($id);
        $shift->delete();

        return $this->successResponse(null, __('Branch shift deleted'), 200);
    }

    #[OA\Get(
        path: '/v1/branches/{branch}/shifts/trashed',
        summary: '🗑️ عرض الورديات المحذوفة (سلة المهملات)',
        description: 'جلب قائمة بالورديات المحذوفة لفرع معين.',
        tags: ['Branch Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم جلب الورديات المحذوفة بنجاح')]
    public function trashed(Request $request, $branchId)
    {
        $query = BranchShift::onlyTrashed()->where('branch_id', $branchId);

        if ($request->input('per_page') === 'all' || $request->boolean('all') || $request->input('paginate') === 'false') {
            $shifts = $query->get();
        } else {
            $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
            $shifts = $query->paginate($perPage);
        }

        return $this->successResponse($shifts, __('Trashed branch shifts retrieved'));
    }

    #[OA\Post(
        path: '/v1/branches/{branch}/shifts/{id}/restore',
        summary: '♻️ استرجاع وردية محذوفة',
        description: 'استرجاع الوردية المحذوفة للفرع.',
        tags: ['Branch Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الوردية', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الوردية بنجاح')]
    public function restore($branchId, $id)
    {
        $shift = BranchShift::onlyTrashed()->where('branch_id', $branchId)->findOrFail($id);
        $shift->restore();
        return $this->successResponse($shift, __('Branch shift restored successfully'));
    }

    #[OA\Put(
        path: '/v1/branches/{branch}/shifts/{shift}',
        summary: '✏️ تعديل وردية',
        description: 'تعديل وردية فرع موجودة.',
        tags: ['Branch Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'shift', in: 'path', required: true, description: 'معرف الوردية', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'اسم الوردية', example: 'وردية المساء'),
                new OA\Property(property: 'start_time', type: 'string', format: 'time', example: '16:00'),
                new OA\Property(property: 'end_time', type: 'string', format: 'time', example: '23:59'),
                new OA\Property(property: 'gender_allowed', type: 'string', description: 'male, female, mixed', example: 'mixed')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تعديل الوردية بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch shift updated'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الوردية', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateBranchShiftRequest $request, $branchId, $id)
    {
        $shift = BranchShift::where('branch_id', $branchId)->findOrFail($id);
        $shift->update($request->validated());

        return $this->successResponse($shift, __('Branch shift updated'));
    }
}
