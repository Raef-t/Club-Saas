<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\AttendanceManager\Services\StaffAttendanceService;
use Modules\AttendanceManager\Http\Requests\StaffCheckInRequest;
use Modules\AttendanceManager\Http\Resources\AttendanceResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Exception;
use Modules\AttendanceManager\Models\Attendance;
use OpenApi\Attributes as OA;

class StaffAttendanceController extends BaseController
{
    protected $attendanceService;

    public function __construct(StaffAttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    #[OA\Get(
        path: '/v1/staff-attendances',
        summary: '📋 عرض حضور وانصراف الموظفين',
        description: 'استرجاع سجل حضور وانصراف جميع الموظفين.',
        tags: ['Staff Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع البيانات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff attendance retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $records = Attendance::where('attendable_type', 'staff')
            ->orderBy('check_in_at', 'desc')
            ->paginate($perPage);

        return $this->successResponse(
            AttendanceResource::collection($records),
            __('Staff attendance retrieved successfully')
        );
    }

    #[OA\Get(
        path: '/v1/staff-attendances/{id}',
        summary: '🔍 تفاصيل الحضور والانصراف',
        description: 'استرجاع تفاصيل سجل حضور وانصراف محدد.',
        tags: ['Staff Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل السجل',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        try {
            $record = $this->attendanceService->getById($id);
            return $this->successResponse(new AttendanceResource($record), __('Retrieved successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    #[OA\Post(
        path: '/v1/staff-attendances',
        summary: '➕ إضافة سجل حضور',
        description: 'إضافة سجل حضور يدوياً للموظف.',
        tags: ['Staff Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['staff_id', 'check_in_at'],
            properties: [
                new OA\Property(property: 'staff_id', type: 'integer', example: 1),
                new OA\Property(property: 'check_in_at', type: 'string', format: 'date-time', example: '2023-10-01T08:00:00Z'),
                new OA\Property(property: 'check_out_at', type: 'string', format: 'date-time', example: '2023-10-01T17:00:00Z'),
                new OA\Property(property: 'club_id', type: 'integer', example: 1),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم الإنشاء بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Error creating record.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(Request $request)
    {
        try {
            $record = $this->attendanceService->create($request->all());
            return $this->successResponse(new AttendanceResource($record), __('Created successfully'), 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Put(
        path: '/v1/staff-attendances/{id}',
        summary: '✏️ تعديل سجل الحضور',
        description: 'تعديل بيانات سجل حضور وانصراف الموظف.',
        tags: ['Staff Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'check_out_at', type: 'string', format: 'date-time', example: '2023-10-01T18:00:00Z')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التعديل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Error updating record.')]))]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(Request $request, $id)
    {
        try {
            $record = $this->attendanceService->update($id, $request->all());
            return $this->successResponse(new AttendanceResource($record), __('Updated successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Delete(
        path: '/v1/staff-attendances/{id}',
        summary: '🗑️ حذف سجل الحضور',
        description: 'حذف سجل الحضور والانصراف من النظام.',
        tags: ['Staff Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Error deleting record.')]))]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id)
    {
        try {
            $this->attendanceService->delete($id);
            return $this->successResponse(null, __('Deleted successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/staff/{staffId}/check-in',
        summary: '✅ تسجيل الحضور للموظف',
        description: 'تسجيل دخول/حضور الموظف في النادي أو الفرع.',
        tags: ['Staff Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staffId', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['club_id', 'branch_id'],
            properties: [
                new OA\Property(property: 'club_id', type: 'integer', example: 1),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                new OA\Property(property: 'facility_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تسجيل الدخول بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff checked in successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Staff already checked in.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function checkIn(StaffCheckInRequest $request, $staffId)
    {
        try {
            $facilityId = $request->input('facility_id');
            $clubId = $request->input('club_id');
            $branchId = $request->input('branch_id');

            $attendance = $this->attendanceService->checkIn((int)$staffId, (int)$clubId, (int)$branchId, $facilityId);
            
            return $this->successResponse(new AttendanceResource($attendance), __('Staff checked in successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/staff/check-out/{attendanceId}',
        summary: '🚪 تسجيل الانصراف',
        description: 'تسجيل خروج/انصراف الموظف.',
        tags: ['Staff Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'attendanceId', in: 'path', required: true, description: 'معرف سجل الحضور', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم تسجيل الانصراف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff checked out successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Staff already checked out.')]))]
    #[OA\Response(response: 404, description: '🚫 السجل غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function checkOut(Request $request, $attendanceId)
    {
        try {
            $attendance = $this->attendanceService->checkOut((int)$attendanceId);
            return $this->successResponse(new AttendanceResource($attendance), __('Staff checked out successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Get(
        path: '/v1/staff/{staffId}/history',
        summary: '📆 سجل حضور وانصراف موظف',
        description: 'استرجاع تاريخ الحضور والانصراف لموظف محدد مع إمكانية الفلترة بالتواريخ.',
        tags: ['Staff Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staffId', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'from', in: 'query', required: false, description: 'تاريخ البداية (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2023-10-01'))]
    #[OA\Parameter(name: 'to', in: 'query', required: false, description: 'تاريخ النهاية (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2023-10-31'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع السجل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff attendance history retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function history(Request $request, $staffId)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $perPage = $request->input('per_page', 15);
        
        $query = $this->attendanceService->getHistory((int)$staffId, $from, $to);
        $history = $query->paginate($perPage);

        return $this->successResponse(AttendanceResource::collection($history), __('Staff attendance history retrieved'));
    }
}
