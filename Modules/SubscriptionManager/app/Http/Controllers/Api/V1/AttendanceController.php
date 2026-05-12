<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\SubscriptionManager\Services\AttendanceService;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Exception;

class AttendanceController extends BaseController
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    #[OA\Post(
        path: '/v1/attendance/check-in',
        summary: '📡 Check-in a member by barcode',
        tags: ['Attendance Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Check-in successful'),
            new OA\Response(response: 422, description: 'Validation or business logic error')
        ]
    )]
    public function checkIn(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
            'activity_id' => 'nullable|exists:activities,id',
        ]);

        try {
            $result = $this->attendanceService->processCheckIn(
                $request->barcode,
                $request->activity_id
            );

            return $this->successResponse($result, __('Check-in successful'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    #[OA\Post(
        path: '/v1/attendance/check-out/{id}',
        summary: '👋 Check-out a member',
        tags: ['Attendance Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Check-out successful'),
            new OA\Response(response: 422, description: 'Error')
        ]
    )]
    public function checkOut($id)
    {
        try {
            $attendance = $this->attendanceService->processCheckOut($id);
            return $this->successResponse($attendance, __('Check-out successful'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
