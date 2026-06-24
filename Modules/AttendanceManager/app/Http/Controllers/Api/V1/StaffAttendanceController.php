<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use Modules\AttendanceManager\Models\Attendance;
use Modules\AttendanceManager\Services\UnifiedAttendanceService;
use Modules\AttendanceManager\Http\Requests\StaffCheckInRequest;
use Modules\AttendanceManager\Http\Resources\AttendanceResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class StaffAttendanceController extends BaseController
{
    public function __construct(protected UnifiedAttendanceService $attendanceService) {}

    #[OA\Get(
        path: '/v1/staff-attendances',
        summary: '📋 عرض حضور وانصراف الموظفين',
        description: 'استرجاع سجل حضور وانصراف جميع الموظفين (بما فيهم الكوتشات).',
        tags: ['Staff Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع البيانات بنجاح', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))]))]
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $records = Attendance::where('attendable_type', 'staff')
            ->orderByDesc('check_in_at')
            ->paginate($perPage);

        return $this->successResponse(
            AttendanceResource::collection($records),
            __('Staff attendance retrieved successfully')
        );
    }

    #[OA\Get(path: '/v1/staff-attendances/{id}', summary: '🔍 تفاصيل الحضور', tags: ['Staff Attendance'], security: [['bearerAuth' => []]])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅', content: new OA\JsonContent())]
    public function show($id)
    {
        try {
            $record = Attendance::where('attendable_type', 'staff')->findOrFail($id);
            return $this->successResponse(new AttendanceResource($record), __('Retrieved successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    #[OA\Post(path: '/v1/staff-attendances', summary: '➕ إضافة حضور يدوي للموظف', tags: ['Staff Attendance'], security: [['bearerAuth' => []]])]
    #[OA\Response(response: 201, description: '✅ تم الإنشاء', content: new OA\JsonContent())]
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $record = Attendance::create([
                'club_id'         => $data['club_id']   ?? null,
                'attendable_type' => 'staff',
                'attendable_id'   => $data['staff_id']  ?? $data['attendable_id'] ?? null,
                'branch_id'       => $data['branch_id'] ?? null,
                'check_in_at'     => $data['check_in_at'] ?? now(),
                'check_out_at'    => $data['check_out_at'] ?? null,
                'status'          => $data['status'] ?? 'checked_in',
                'metadata'        => $data['metadata'] ?? [],
            ]);
            return $this->successResponse(new AttendanceResource($record), __('Created successfully'), 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Put(path: '/v1/staff-attendances/{id}', summary: '✏️ تعديل حضور موظف', tags: ['Staff Attendance'], security: [['bearerAuth' => []]])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ تم التعديل', content: new OA\JsonContent())]
    public function update(Request $request, $id)
    {
        try {
            $record = Attendance::where('attendable_type', 'staff')->findOrFail($id);
            $record->update($request->only(['check_in_at', 'check_out_at', 'status', 'metadata']));
            return $this->successResponse(new AttendanceResource($record), __('Updated successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Delete(path: '/v1/staff-attendances/{id}', summary: '🗑️ حذف حضور موظف', tags: ['Staff Attendance'], security: [['bearerAuth' => []]])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ تم الحذف', content: new OA\JsonContent())]
    public function destroy($id)
    {
        try {
            Attendance::where('attendable_type', 'staff')->findOrFail($id)->delete();
            return $this->successResponse(null, __('Deleted successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

}
