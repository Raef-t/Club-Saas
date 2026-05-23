<?php
namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Services\SubscriptionFreezeService;
use Modules\SubscriptionManager\Http\Requests\StoreSubscriptionFreezeRequest;
use Modules\SubscriptionManager\Http\Requests\UpdateSubscriptionFreezeRequest;
use Modules\SubscriptionManager\Http\Resources\SubscriptionFreezeResource;

class SubscriptionFreezeController extends BaseController
{
    protected $service;

    public function __construct(SubscriptionFreezeService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->successResponse(SubscriptionFreezeResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    public function store(StoreSubscriptionFreezeRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new SubscriptionFreezeResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new SubscriptionFreezeResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdateSubscriptionFreezeRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new SubscriptionFreezeResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
