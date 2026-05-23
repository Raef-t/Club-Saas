<?php
namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\StaffManager\Services\PayslipService;
use Modules\StaffManager\Http\Requests\StorePayslipRequest;
use Modules\StaffManager\Http\Requests\UpdatePayslipRequest;
use Modules\StaffManager\Http\Resources\PayslipResource;

class PayslipController extends BaseController
{
    protected $service;

    public function __construct(PayslipService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->successResponse(PayslipResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    public function store(StorePayslipRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new PayslipResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new PayslipResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdatePayslipRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new PayslipResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
