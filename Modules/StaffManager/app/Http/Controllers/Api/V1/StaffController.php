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
        $staff = $this->staffService->getAllStaff();
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


    #[OA\Get(
        path: '/v1/staff/{id}',
        summary: '🔍 Get staff member details',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function show($id)
    {
        $staff = $this->staffService->getStaffById($id);
        return $this->successResponse(new StaffResource($staff), __('Staff retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/staff/{id}',
        summary: '✏️ Update a staff member',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Staff updated')
        ]
    )]
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'full_name' => 'nullable|string|max:200',
            'mobile_1' => 'nullable|string',
            'email' => 'nullable|email',
            'role' => 'nullable|in:admin,receptionist,coach,cleaner,manager',
            'employment_type' => 'nullable|in:fixed_salary,commission_based,hybrid',
            'base_salary' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'specialization' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'contract_type' => 'nullable|in:probation,permanent',
            'work_type' => 'nullable|in:part_time,full_time',
            'work_status' => 'nullable|in:active,suspended,on_leave',
            'salary_type' => 'nullable|in:monthly,commission,weekly',
            'employee_type' => 'nullable|in:receptionist,equipment_coach,cleaner,accountant,manager,supervisor,nursery',
            'other_tasks' => 'nullable|string|max:500',
            'gym_type' => 'nullable|in:male,female,mixed',
            'shift_type' => 'nullable|in:part_time,full_time',
            'certificates_held' => 'nullable|array',
        ]);

        $staff = $this->staffService->updateStaff($id, $data);
        return $this->successResponse(new StaffResource($staff), __('Staff updated successfully'));
    }


    #[OA\Patch(
        path: '/v1/staff/{id}/toggle-status',
        summary: '🔄 Toggle staff active/inactive status',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Status toggled')
        ]
    )]
    public function toggleStatus($id)
    {
        $staff = $this->staffService->toggleStatus($id);
        return $this->successResponse(new StaffResource($staff), __('Status toggled successfully'));
    }

    #[OA\Post(
        path: '/v1/staff/{id}/sync-branches',
        summary: '🔄 Sync multiple branches for staff',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Branches synced')
        ]
    )]
    public function syncBranches(Request $request, $id)
    {
        $validated = $request->validate([
            'branch_ids' => 'required|array',
            'branch_ids.*' => 'integer',
        ]);

        $staff = \Modules\StaffManager\Models\Staff::findOrFail($id);
        
        // Zero code-coupling: Delete existing and insert new instead of relying on Eloquent relationships with foreign modules
        \Modules\StaffManager\Models\StaffBranch::where('staff_id', $staff->id)->delete();
        
        $inserts = array_map(function($branchId) use ($staff) {
            return [
                'staff_id' => $staff->id,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $validated['branch_ids']);
        
        \Modules\StaffManager\Models\StaffBranch::insert($inserts);

        return $this->successResponse(null, __('Branches synced successfully'));
    }


    #[OA\Delete(
        path: '/v1/staff/{id}',
        summary: '🗑 Soft delete a staff member',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Staff deleted')
        ]
    )]
    public function destroy($id)
    {
        $this->staffRepository->delete($id);
        return $this->successResponse(null, __('Staff deleted successfully'));
    }
}
