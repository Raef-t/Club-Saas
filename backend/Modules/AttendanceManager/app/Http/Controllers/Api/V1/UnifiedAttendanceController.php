<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\AttendanceManager\Services\UnifiedAttendanceService;
use Modules\AttendanceManager\Http\Requests\UnifiedCheckInRequest;
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
    #[OA\RequestBody(required: true, content: new OA\JsonContent(required: ['attendable_type', 'attendable_id', 'branch_id'], properties: [new OA\Property(property: 'attendable_type', type: 'string', enum: ['member', 'staff'], example: 'member'), new OA\Property(property: 'attendable_id', type: 'integer', example: 1), new OA\Property(property: 'branch_id', type: 'integer', example: 1), new OA\Property(property: 'facility_id', type: 'integer', example: 1), new OA\Property(property: 'check_in_at', type: 'string', format: 'date-time', example: '2026-06-26 15:30:00')]))]
    #[OA\Response(response: 200, description: '✅ تم تسجيل الدخول', content: new OA\JsonContent())]
    public function checkIn(UnifiedCheckInRequest $request)
    {
        try {
            $type = $request->input('attendable_type');
            $id   = (int) $request->input('attendable_id');
            $metadata = $request->input('metadata', []);

            if ($request->has('facility_id')) {
                $metadata['facility_id'] = $request->input('facility_id');
            }

            if ($request->has('check_in_at')) {
                $metadata['check_in_at'] = \Carbon\Carbon::parse($request->input('check_in_at'))->toDateTimeString();
            }

            // Receptionist-selected subscription (for manual session deduction)
            if ($request->has('subscription_id')) {
                $metadata['subscription_id'] = (int) $request->input('subscription_id');
            }

            // Locker key assigned to the player
            if ($request->has('locker_id')) {
                $metadata['locker_id'] = (int) $request->input('locker_id');
            }

            $branch = \Illuminate\Support\Facades\DB::table('branches')->where('id', $request->input('branch_id'))->first();
            if (!$branch) {
                return $this->errorResponse('Branch not found.', 404);
            }

            $attendance = $this->attendanceService->checkIn(
                type: $type,
                entityId: $id,
                clubId: (int) $branch->club_id,
                branchId: (int) $branch->id,
                metadata: $metadata
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
    #[OA\Response(response: 200, description: '✅ تم الانصراف', content: new OA\JsonContent())]
    public function checkOut($attendanceId)
    {
        try {
            $attendance = $this->attendanceService->checkOut((int) $attendanceId);
            return $this->successResponse(new AttendanceResource($attendance), __('Checked out successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Get(
        path: '/v1/attendances/history',
        summary: '📆 سجل حضور موحد',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'attendable_type', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['member', 'staff']))]
    #[OA\Parameter(name: 'attendable_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅', content: new OA\JsonContent())]
    public function history(Request $request)
    {
        $request->validate([
            'attendable_type' => 'required|string|in:member,staff',
            'attendable_id'   => 'required|integer',
        ]);

        $query = $this->attendanceService->getHistory(
            $request->input('attendable_type'),
            (int) $request->input('attendable_id'),
            $request->input('from'),
            $request->input('to')
        );

        $history = $query->paginate($request->input('per_page', 15));

        return $this->successResponse(AttendanceResource::collection($history), __('Attendance history retrieved'));
    }
}
