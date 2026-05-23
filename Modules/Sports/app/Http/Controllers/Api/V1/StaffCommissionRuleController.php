<?php
namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\Sports\Services\StaffCommissionRuleService;
use Modules\Sports\Http\Requests\StoreStaffCommissionRuleRequest;
use Modules\Sports\Http\Requests\UpdateStaffCommissionRuleRequest;
use Modules\Sports\Http\Resources\StaffCommissionRuleResource;

class StaffCommissionRuleController extends BaseController
{
    protected $service;

    public function __construct(StaffCommissionRuleService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->successResponse(StaffCommissionRuleResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    public function store(StoreStaffCommissionRuleRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new StaffCommissionRuleResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new StaffCommissionRuleResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdateStaffCommissionRuleRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new StaffCommissionRuleResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
