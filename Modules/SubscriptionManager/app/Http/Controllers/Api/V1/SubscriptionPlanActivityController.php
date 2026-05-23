<?php
namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Services\SubscriptionPlanActivityService;
use Modules\SubscriptionManager\Http\Requests\StoreSubscriptionPlanActivityRequest;
use Modules\SubscriptionManager\Http\Requests\UpdateSubscriptionPlanActivityRequest;
use Modules\SubscriptionManager\Http\Resources\SubscriptionPlanActivityResource;

class SubscriptionPlanActivityController extends BaseController
{
    protected $service;

    public function __construct(SubscriptionPlanActivityService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->successResponse(SubscriptionPlanActivityResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    public function store(StoreSubscriptionPlanActivityRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new SubscriptionPlanActivityResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new SubscriptionPlanActivityResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdateSubscriptionPlanActivityRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new SubscriptionPlanActivityResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
