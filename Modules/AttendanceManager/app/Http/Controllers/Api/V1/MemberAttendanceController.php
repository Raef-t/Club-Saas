<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\AttendanceManager\Services\MemberAttendanceService;
use Modules\AttendanceManager\Http\Requests\MemberCheckInRequest;
use Modules\AttendanceManager\Http\Resources\AttendanceResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Exception;
use Modules\AttendanceManager\Models\Attendance;

class MemberAttendanceController extends BaseController
{
    protected $attendanceService;

    public function __construct(MemberAttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $records = Attendance::where('attendable_type', 'player_subscription')
            ->orderBy('check_in_at', 'desc')
            ->paginate($perPage);

        return $this->successResponse(
            AttendanceResource::collection($records),
            __('Member attendance retrieved successfully')
        );
    }

    public function show($id)
    {
        try {
            $record = $this->attendanceService->getById($id);
            return $this->successResponse(new AttendanceResource($record), __('Retrieved successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $record = $this->attendanceService->create($request->all());
            return $this->successResponse(new AttendanceResource($record), __('Created successfully'), 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $record = $this->attendanceService->update($id, $request->all());
            return $this->successResponse(new AttendanceResource($record), __('Updated successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $this->attendanceService->delete($id);
            return $this->successResponse(null, __('Deleted successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function checkIn(MemberCheckInRequest $request)
    {
        try {
            $memberId = $request->input('member_id');
            $facilityId = $request->input('facility_id');
            $clubId = $request->input('club_id');
            $branchId = $request->input('branch_id');

            $attendance = $this->attendanceService->checkIn((int)$memberId, (int)$clubId, (int)$branchId, $facilityId);
            
            return $this->successResponse(new AttendanceResource($attendance), __('Member checked in successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function checkOut(Request $request, $attendanceId)
    {
        try {
            $attendance = $this->attendanceService->checkOut((int)$attendanceId);
            return $this->successResponse(new AttendanceResource($attendance), __('Member checked out successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function history(Request $request, $memberId)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $perPage = $request->input('per_page', 15);
        
        $query = $this->attendanceService->getHistory((int)$memberId, $from, $to);
        $history = $query->paginate($perPage);

        return $this->successResponse(AttendanceResource::collection($history), __('Member attendance history retrieved'));
    }
}
