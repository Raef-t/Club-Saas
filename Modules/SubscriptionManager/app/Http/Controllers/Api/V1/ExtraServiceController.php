<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Modules\SubscriptionManager\Models\ExtraService;
use Modules\SubscriptionManager\Http\Requests\StoreExtraServiceRequest;
use Modules\SubscriptionManager\Http\Requests\UpdateExtraServiceRequest;
use Modules\SubscriptionManager\Http\Resources\ExtraServiceResource;

class ExtraServiceController extends Controller
{
    public function index()
    {
        $services = ExtraService::all();
        return ExtraServiceResource::collection($services);
    }

    public function store(StoreExtraServiceRequest $request)
    {
        $service = ExtraService::create($request->validated());
        return new ExtraServiceResource($service);
    }

    public function show($id)
    {
        $service = ExtraService::findOrFail($id);
        return new ExtraServiceResource($service);
    }

    public function update(UpdateExtraServiceRequest $request, $id)
    {
        $service = ExtraService::findOrFail($id);
        $service->update($request->validated());
        return new ExtraServiceResource($service);
    }

    public function destroy($id)
    {
        $service = ExtraService::findOrFail($id);
        $service->delete();
        return response()->json(null, 204);
    }
}

