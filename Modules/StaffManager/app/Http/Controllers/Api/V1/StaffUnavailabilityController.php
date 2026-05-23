<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StaffManager\Models\StaffUnavailability;
use Modules\StaffManager\Models\Staff;

class StaffUnavailabilityController extends Controller
{
    public function index($staffId)
    {
        $unavailabilities = StaffUnavailability::where('staff_id', $staffId)->get();
        return response()->json($unavailabilities);
    }

    public function store(Request $request, $staffId)
    {
        $validated = $request->validate([
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'reason' => 'nullable|string',
        ]);

        $validated['staff_id'] = $staffId;
        $unavailability = StaffUnavailability::create($validated);

        return response()->json($unavailability, 201);
    }

    public function destroy($staffId, $id)
    {
        $unavailability = StaffUnavailability::where('staff_id', $staffId)->findOrFail($id);
        $unavailability->delete();

        return response()->json(null, 204);
    }
}
