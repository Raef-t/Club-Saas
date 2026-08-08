<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\AttendanceManager\Services\UnifiedAttendanceService;
use Modules\AttendanceManager\Http\Requests\UnifiedCheckInRequest;
use Modules\AttendanceManager\Http\Requests\BulkCheckOutRequest;
use Modules\AttendanceManager\Http\Resources\AttendanceResource;
use OpenApi\Attributes as OA;

class UnifiedAttendanceController extends BaseController
{
    public function __construct(protected UnifiedAttendanceService $attendanceService) {}

    #[OA\Post(
        path: '/v1/attendances/check-in',
        summary: '✅ تسجيل الدخول الموحد (لكافة أنواع المستخدمين)',
        description: 'تسجيل دخول عضو أو موظف أو كوتش باختيار نوع attendable_type.',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['attendable_type', 'attendable_id', 'branch_id'],
            properties: [
                new OA\Property(property: 'attendable_type', type: 'string', enum: ['member', 'staff'], example: 'member'),
                new OA\Property(property: 'attendable_id', type: 'integer', example: 1),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                new OA\Property(property: 'facility_id', type: 'integer', example: 1, description: 'معرف المنشأة (اختياري)'),
                new OA\Property(property: 'check_in_at', type: 'string', format: 'date-time', example: '2026-08-08 14:30:00', description: 'تاريخ ووقت تسجيل الدخول اليدوي (اختياري)')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم تسجيل الدخول', content: new OA\JsonContent())]
    public function checkIn(UnifiedCheckInRequest $request)
    {
        try {
            $type = $request->input('attendable_type');
            $id   = (int) $request->input('attendable_id');
            $checkInAt = $request->input('check_in_at');
            $branch = \Illuminate\Support\Facades\DB::table('branches')->where('id', $request->input('branch_id'))->first();
            if (!$branch) {
                return $this->errorResponse('Branch not found.', 404);
            }

            $attendance = $this->attendanceService->checkIn(
                type: $type,
                entityId: $id,
                branchId: (int) $branch->id,
                checkInAt: $checkInAt
            );

            return $this->successResponse(new AttendanceResource($attendance), __('Checked in successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/attendances/check-out/{attendanceId}',
        summary: '🚪 تسجيل الانصراف الموحد',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'attendanceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'check_out_at', type: 'string', format: 'date-time', example: '2026-08-08 16:30:00', description: 'تاريخ ووقت تسجيل الانصراف اليدوي (اختياري، إذا لم يرسل يأخذ الوقت الحالي بالسيرفر)')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم الانصراف', content: new OA\JsonContent())]
    public function checkOut(Request $request, $attendanceId)
    {
        try {
            $checkOutAt = $request->input('check_out_at');
            $attendance = $this->attendanceService->checkOut((int) $attendanceId, $checkOutAt);
            return $this->successResponse(new AttendanceResource($attendance), __('Checked out successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/attendances/bulk-check-out',
        summary: '🚪 تسجيل الانصراف الجماعي (حسب الفرع والنشاط/الخطة)',
        description: 'تسجيل الانصراف الجماعي لجميع الأشخاص الذين سجلوا دخولاً فقط ولم يسجلوا خروجاً بعد في فرع معين لخطة اشتراك محددة.',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['branch_id', 'subscription_plan_id'],
            properties: [
                new OA\Property(property: 'branch_id', type: 'integer', example: 1, description: 'معرف الفرع'),
                new OA\Property(property: 'subscription_plan_id', type: 'integer', example: 5, description: 'معرف خطة الاشتراك / النشاط (مثل الأيروبيك)')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تنفيذ الانصراف الجماعي بنجاح',
        content: new OA\JsonContent()
    )]
    public function bulkCheckOut(BulkCheckOutRequest $request)
    {
        try {
            $result = $this->attendanceService->bulkCheckOut(
                branchId: (int) $request->input('branch_id'),
                subscriptionPlanId: (int) $request->input('subscription_plan_id')
            );

            $result['successful'] = AttendanceResource::collection($result['successful']);

            return $this->successResponse($result, __('Bulk check-out process completed'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Get(
        path: '/v1/attendances/history',
        summary: '📆 سجل حضور موحد',
        description: 'يجلب سجل حضور وانصراف (عضو / موظف / الكل) مع إمكانية الفلترة حسب الشخص وتاريخ البداية والنهاية. (تم إلغاء الترقيم - Pagination وجلب كافة البيانات).',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'attendable_type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['all', 'member', 'staff'], default: 'all'), description: 'نوع المستخدم (member, staff, أو all للجلب الشامل)')]
    #[OA\Parameter(name: 'attendable_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'معرف المستخدم (اختياري - إذا لم يحدد يجلب الكل)')]
    #[OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-01'), description: 'تاريخ بداية الفلترة بصيغة YYYY-MM-DD (اختياري)')]
    #[OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-07-21'), description: 'تاريخ نهاية الفلترة بصيغة YYYY-MM-DD (اختياري)')]
    #[OA\Response(response: 200, description: '✅', content: new OA\JsonContent())]
    public function history(Request $request)
    {
        $request->validate([
            'attendable_type' => 'nullable|string|in:all,member,staff',
            'attendable_id'   => 'nullable|integer',
            'from'            => 'nullable|date_format:Y-m-d',
            'to'              => 'nullable|date_format:Y-m-d',
        ]);

        $type = $request->input('attendable_type', 'all');
        $entityId = $request->filled('attendable_id') ? (int) $request->input('attendable_id') : null;

        $query = $this->attendanceService->getHistory(
            $type,
            $entityId,
            $request->input('from'),
            $request->input('to')
        );

        // إزالة Pagination وجلب جميع السجلات بناءً على الفلترة
        $history = $query->get();

        return $this->successResponse(AttendanceResource::collection($history), __('Attendance history retrieved'));
    }

    #[OA\Delete(
        path: '/v1/attendances/{id}',
        summary: '🗑️ حذف سجل حضور (Soft Delete)',
        description: 'حذف سجل حضور ناعماً من النظام مع كافّة استهلاكات الجلسات المترابطة به ناعماً ومتتابعاً.',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف سجل الحضور', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم حذف سجل الحضور ناعماً بنجاح')]
    #[OA\Response(response: 404, description: '🚫 سجل الحضور غير موجود')]
    public function destroy(int $id)
    {
        $attendance = \Modules\AttendanceManager\Models\Attendance::findOrFail($id);
        $attendance->delete();
        return $this->successResponse(null, __('Attendance deleted successfully'));
    }

    #[OA\Post(
        path: '/v1/attendances/{id}/restore',
        summary: '♻️ استرجاع سجل حضور محذوف',
        description: 'استرجاع سجل الحضور المحذوف ناعماً وكافّة استهلاكات الجلسات المترابطة به تلقائياً.',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف سجل الحضور', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع سجل الحضور بنجاح')]
    #[OA\Response(response: 404, description: '🚫 سجل الحضور غير موجود في سلة المحذوفات')]
    public function restore(int $id)
    {
        $attendance = \Modules\AttendanceManager\Models\Attendance::onlyTrashed()->findOrFail($id);
        $attendance->restore();
        return $this->successResponse(null, __('Attendance restored successfully'));
    }
}
