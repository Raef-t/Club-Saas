<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SubscriptionManager\Models\PlayerSubscriptionService;

class PlayerSubscriptionServiceController extends Controller
{
    public function index()
    {
        $services = PlayerSubscriptionService::all();
        return response()->json($services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'player_subscription_id' => 'required|exists:player_subscriptions,id',
            'extra_service_id' => 'required|exists:extra_services,id',
            'price_charged' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $service = PlayerSubscriptionService::create($validated);
        return response()->json($service, 201);
    }

    public function show($id)
    {
        $service = PlayerSubscriptionService::findOrFail($id);
        return response()->json($service);
    }

    public function update(Request $request, $id)
    {
        $service = PlayerSubscriptionService::findOrFail($id);

        $validated = $request->validate([
            'price_charged' => 'sometimes|required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $service->update($validated);
        return response()->json($service);
    }

    public function destroy($id)
    {
        $service = PlayerSubscriptionService::findOrFail($id);
        $service->delete();
        return response()->json(null, 204);
    }
}
