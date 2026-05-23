<?php
namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\StaffManager\Services\StaffShiftService;
use Modules\StaffManager\Http\Requests\StoreStaffShiftRequest;
use Modules\StaffManager\Http\Requests\UpdateStaffShiftRequest;
use Modules\StaffManager\Http\Resources\StaffShiftResource;

class StaffShiftController extends BaseController
{
    protected $service;

    public function __construct(StaffShiftService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->successResponse(StaffShiftResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    public function store(StoreStaffShiftRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new StaffShiftResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new StaffShiftResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdateStaffShiftRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new StaffShiftResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
