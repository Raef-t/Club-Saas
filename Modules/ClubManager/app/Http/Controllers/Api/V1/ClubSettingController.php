<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\ClubManager\Models\ClubSetting;
use Illuminate\Http\Request;
use Modules\ClubManager\Http\Requests\UpdateClubSettingRequest;

class ClubSettingController extends BaseController
{
    public function index()
    {
        return $this->successResponse(ClubSetting::all(), __('Club settings retrieved'));
    }

    public function show($id)
    {
        $setting = ClubSetting::where('club_id', $id)->firstOrFail();
        return $this->successResponse($setting, __('Club setting retrieved'));
    }

    public function update(UpdateClubSettingRequest $request, $id)
    {
        $setting = ClubSetting::firstOrCreate(['club_id' => $id]);
        
        $validated = $request->validated();

        $setting->update($validated);

        return $this->successResponse($setting, __('Club setting updated'));
    }
}
