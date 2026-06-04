<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Modules\ClubManager\Models\FacilityWorkingHour;
use Modules\ClubManager\Http\Requests\StoreFacilityWorkingHourRequest;

class FacilityWorkingHourController extends BaseController
{
    public function index($facilityId)
    {
        $hours = FacilityWorkingHour::where('facility_id', $facilityId)->get();
        return $this->successResponse($hours, __('Facility working hours retrieved'));
    }

    public function store(StoreFacilityWorkingHourRequest $request, $facilityId)
    {
        $validated = $request->validated();

        $validated['facility_id'] = $facilityId;

        $workingHour = FacilityWorkingHour::updateOrCreate(
            ['facility_id' => $facilityId, 'day_of_week' => $validated['day_of_week']],
            $validated
        );

        return $this->successResponse($workingHour, __('Facility working hours updated'), 201);
    }

    public function destroy($facilityId, $id)
    {
        $workingHour = FacilityWorkingHour::where('facility_id', $facilityId)->findOrFail($id);
        $workingHour->delete();

        return $this->successResponse(null, __('Facility working hour deleted'), 200);
    }
}
