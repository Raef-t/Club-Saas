<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Modules\ClubManager\Models\BranchShift;
use Modules\ClubManager\Http\Requests\StoreBranchShiftRequest;

class BranchShiftController extends BaseController
{
    public function index($branchId)
    {
        $shifts = BranchShift::where('branch_id', $branchId)->get();
        return $this->successResponse($shifts, __('Branch shifts retrieved'));
    }

    public function store(StoreBranchShiftRequest $request, $branchId)
    {
        $validated = $request->validated();
        $validated['branch_id'] = $branchId;

        // Create shift
        $shift = BranchShift::create($validated);

        return $this->successResponse($shift, __('Branch shift created'), 201);
    }

    public function destroy($branchId, $id)
    {
        $shift = BranchShift::where('branch_id', $branchId)->findOrFail($id);
        $shift->delete();

        return $this->successResponse(null, __('Branch shift deleted'), 200);
    }
}
