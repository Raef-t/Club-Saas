<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\AttendanceManager\Services\MemberAttendanceService;
use Modules\AttendanceManager\Http\Requests\MemberCheckInRequest;
use Modules\AttendanceManager\Http\Resources\AttendanceResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Exception;

class MemberAttendanceController extends BaseController
{
    protected $attendanceService;

    public function __construct(MemberAttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index()
    {
        return $this->successResponse(AttendanceResource::collection($this->attendanceService->getAll()), __('Member attendance retrieved successfully'));
    }

    public function show($id)
    {
        return $this->successResponse(new AttendanceResource($this->attendanceService->getById($id)), __('Retrieved successfully'));
    }

    public function store(Request $request)
    {
        $record = $this->attendanceService->create($request->all());
        return $this->successResponse(new AttendanceResource($record), __('Created successfully'), 201);
    }

    public function update(Request $request, $id)
    {
        $record = $this->attendanceService->update($id, $request->all());
        return $this->successResponse(new AttendanceResource($record), __('Updated successfully'));
    }

    public function destroy($id)
    {
        $this->attendanceService->delete($id);
        return $this->successResponse(null, __('Deleted successfully'));
    }

    public function checkIn(MemberCheckInRequest $request)
    {
        try {
            $facilityId = $request->input('facility_id');
            $attendance = $this->attendanceService->checkIn($request->member_id, $facilityId);
            return $this->successResponse(new AttendanceResource($attendance), __('Member checked in successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function checkOut(Request $request, $attendanceId)
    {
        try {
            $attendance = $this->attendanceService->checkOut($attendanceId);
            return $this->successResponse(new AttendanceResource($attendance), __('Member checked out successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function history(Request $request, $memberId)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        
        $history = $this->attendanceService->getHistory($memberId, $from, $to);
        return $this->successResponse(AttendanceResource::collection($history), __('Member attendance history retrieved'));
    }
}
