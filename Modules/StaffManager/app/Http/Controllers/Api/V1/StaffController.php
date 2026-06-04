<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\StaffManager\Services\StaffService;
use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\StaffManager\Http\Resources\StaffResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

use Modules\StaffManager\Http\Requests\StoreStaffRequest;
use Modules\StaffManager\Http\Requests\UpdateStaffRequest;
use Modules\StaffManager\Http\Requests\SetStaffScheduleRequest;
use Modules\StaffManager\Http\Requests\SyncStaffBranchesRequest;

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
    public function index(Request $request)
    {
        $staff = $this->staffService->getAllStaff($request->all());
        return $this->successResponse(
            StaffResource::collection($staff)->response()->getData(true),
            __('Staff retrieved successfully')
        );
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
    public function setSchedule(SetStaffScheduleRequest $request, $id)
    {
        $data = $request->validated();

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
    public function update(UpdateStaffRequest $request, $id)
    {
        $data = $request->validated();

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
    public function syncBranches(SyncStaffBranchesRequest $request, $id)
    {
        $validated = $request->validated();

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
