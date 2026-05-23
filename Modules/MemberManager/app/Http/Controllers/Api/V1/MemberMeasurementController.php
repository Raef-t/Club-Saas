<?php
namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\MemberManager\Services\MemberMeasurementService;
use Modules\MemberManager\Http\Requests\StoreMemberMeasurementRequest;
use Modules\MemberManager\Http\Requests\UpdateMemberMeasurementRequest;
use Modules\MemberManager\Http\Resources\MemberMeasurementResource;

class MemberMeasurementController extends BaseController
{
    protected $service;

    public function __construct(MemberMeasurementService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->successResponse(MemberMeasurementResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    public function store(StoreMemberMeasurementRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new MemberMeasurementResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new MemberMeasurementResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdateMemberMeasurementRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new MemberMeasurementResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
