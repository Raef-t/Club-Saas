<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ClubManager\Models\FacilityWorkingHour;

class FacilityWorkingHourController extends Controller
{
    public function index($facilityId)
    {
        $hours = FacilityWorkingHour::where('facility_id', $facilityId)->get();
        return response()->json($hours);
    }

    public function store(Request $request, $facilityId)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
        ]);

        $validated['facility_id'] = $facilityId;

        $workingHour = FacilityWorkingHour::updateOrCreate(
            ['facility_id' => $facilityId, 'day_of_week' => $validated['day_of_week']],
            $validated
        );

        return response()->json($workingHour, 201);
    }

    public function destroy($facilityId, $id)
    {
        $workingHour = FacilityWorkingHour::where('facility_id', $facilityId)->findOrFail($id);
        $workingHour->delete();

        return response()->json(null, 204);
    }
}
