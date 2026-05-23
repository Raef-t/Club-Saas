<?php
namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\Sports\Services\StaffActivityService;
use Modules\Sports\Http\Requests\StoreStaffActivityRequest;
use Modules\Sports\Http\Requests\UpdateStaffActivityRequest;
use Modules\Sports\Http\Resources\StaffActivityResource;

class StaffActivityController extends BaseController
{
    protected $service;

    public function __construct(StaffActivityService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->successResponse(StaffActivityResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    public function store(StoreStaffActivityRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new StaffActivityResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new StaffActivityResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdateStaffActivityRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new StaffActivityResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
