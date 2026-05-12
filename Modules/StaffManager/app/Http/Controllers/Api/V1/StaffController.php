<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\StaffManager\Services\StaffService;
use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\StaffManager\Http\Resources\StaffResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

use Modules\StaffManager\Http\Requests\StoreStaffRequest;

class StaffController extends BaseController
{
    protected $staffService;
    protected $staffRepository;

    public function __construct(StaffService $staffService, StaffRepositoryInterface $staffRepository)
    {
        $this->staffService = $staffService;
        $this->staffRepository = $staffRepository;
    }

    #[OA\Get(
        path: '/v1/staff',
        summary: '👥 List all staff and coaches',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        $staff = $this->staffRepository->all();
        return $this->successResponse(StaffResource::collection($staff), __('Staff retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/staff',
        summary: '➕ Onboard a new staff member',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Staff onboarded')
        ]
    )]
    public function store(StoreStaffRequest $request)
    {
        $staff = $this->staffService->onboardStaff($request->validated());
        return $this->successResponse(new StaffResource($staff), __('Staff onboarded successfully'), 201);
    }

    #[OA\Post(
        path: '/v1/staff/{id}/schedule',
        summary: '📅 Set staff weekly schedule',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Schedule updated')
        ]
    )]
    public function setSchedule(Request $request, $id)
    {
        $data = $request->validate([
            'shifts' => 'required|array',
            'shifts.*.day_of_week' => 'required|integer|min:0|max:6',
            'shifts.*.start_time' => 'required|date_format:H:i',
            'shifts.*.end_time' => 'required|date_format:H:i|after:shifts.*.start_time',
        ]);

        $staff = $this->staffService->setStaffSchedule($id, $data['shifts']);
        return $this->successResponse(new StaffResource($staff), __('Schedule updated successfully'));
    }

    #[OA\Post(
        path: '/v1/staff/{id}/check-in',
        summary: '🕒 Staff check-in',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Check-in successful')
        ]
    )]
    public function checkIn($id)
    {
        $attendance = $this->staffService->checkIn($id);
        return $this->successResponse($attendance, __('Check-in successful'));
    }
}
