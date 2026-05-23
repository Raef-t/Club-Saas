<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SubscriptionManager\Models\ExtraService;

class ExtraServiceController extends Controller
{
    public function index()
    {
        $services = ExtraService::all();
        return response()->json($services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $service = ExtraService::create($validated);
        return response()->json($service, 201);
    }

    public function show($id)
    {
        $service = ExtraService::findOrFail($id);
        return response()->json($service);
    }

    public function update(Request $request, $id)
    {
        $service = ExtraService::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'default_price' => 'sometimes|required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $service->update($validated);
        return response()->json($service);
    }

    public function destroy($id)
    {
        $service = ExtraService::findOrFail($id);
        $service->delete();
        return response()->json(null, 204);
    }
}
