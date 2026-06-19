<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Modules\ClubManager\Models\BranchShift;
use Modules\ClubManager\Http\Requests\StoreBranchShiftRequest;
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
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الورديات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch shifts retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'الوردية الصباحية'),
                    new OA\Property(property: 'start_time', type: 'string', example: '08:00:00'),
                    new OA\Property(property: 'end_time', type: 'string', example: '16:00:00')
                ]))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index($branchId)
    {
        $shifts = BranchShift::where('branch_id', $branchId)->get();
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
            required: ['name', 'start_time', 'end_time'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'الوردية المسائية'),
                new OA\Property(property: 'start_time', type: 'string', format: 'time', example: '16:00:00'),
                new OA\Property(property: 'end_time', type: 'string', format: 'time', example: '23:59:59')
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
        summary: '🗑️ حذف الوردية',
        description: 'إزالة وردية محددة من الفرع.',
        tags: ['Branch Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'shift', in: 'path', required: true, description: 'معرف الوردية', schema: new OA\Schema(type: 'integer', example: 10))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branch shift deleted'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الوردية', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($branchId, $id)
    {
        $shift = BranchShift::where('branch_id', $branchId)->findOrFail($id);
        $shift->delete();

        return $this->successResponse(null, __('Branch shift deleted'), 200);
    }
}
