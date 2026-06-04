<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Modules\SubscriptionManager\Models\PlayerSubscriptionService;
use Modules\SubscriptionManager\Http\Requests\StorePlayerSubscriptionServiceRequest;
use Modules\SubscriptionManager\Http\Requests\UpdatePlayerSubscriptionServiceRequest;
use Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionServiceResource;

class PlayerSubscriptionServiceController extends Controller
{
    public function index()
    {
        $services = PlayerSubscriptionService::all();
        return PlayerSubscriptionServiceResource::collection($services);
    }

    public function store(StorePlayerSubscriptionServiceRequest $request)
    {
        $service = PlayerSubscriptionService::create($request->validated());
        return new PlayerSubscriptionServiceResource($service);
    }

    public function show($id)
    {
        $service = PlayerSubscriptionService::findOrFail($id);
        return new PlayerSubscriptionServiceResource($service);
    }

    public function update(UpdatePlayerSubscriptionServiceRequest $request, $id)
    {
        $service = PlayerSubscriptionService::findOrFail($id);
        $service->update($request->validated());
        return new PlayerSubscriptionServiceResource($service);
    }

    public function destroy($id)
    {
        $service = PlayerSubscriptionService::findOrFail($id);
        $service->delete();
        return response()->json(null, 204);
    }
}

