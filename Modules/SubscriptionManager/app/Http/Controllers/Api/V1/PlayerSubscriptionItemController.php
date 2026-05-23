<?php
namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Services\PlayerSubscriptionItemService;
use Modules\SubscriptionManager\Http\Requests\StorePlayerSubscriptionItemRequest;
use Modules\SubscriptionManager\Http\Requests\UpdatePlayerSubscriptionItemRequest;
use Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionItemResource;

class PlayerSubscriptionItemController extends BaseController
{
    protected $service;

    public function __construct(PlayerSubscriptionItemService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->successResponse(PlayerSubscriptionItemResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    public function store(StorePlayerSubscriptionItemRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new PlayerSubscriptionItemResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new PlayerSubscriptionItemResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdatePlayerSubscriptionItemRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new PlayerSubscriptionItemResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
