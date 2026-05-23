<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ClubManager\Models\ClubSetting;

class ClubSettingController extends Controller
{
    public function index()
    {
        return response()->json(ClubSetting::all());
    }

    public function show($id)
    {
        $setting = ClubSetting::where('club_id', $id)->firstOrFail();
        return response()->json($setting);
    }

    public function update(Request $request, $id)
    {
        $setting = ClubSetting::firstOrCreate(['club_id' => $id]);
        
        $validated = $request->validate([
            'theme_colors' => 'sometimes|array',
            'language' => 'sometimes|string|in:ar,en,all',
            'enabled_features' => 'sometimes|array',
            'bg_image_url' => 'sometimes|string|nullable'
        ]);

        $setting->update($validated);

        return response()->json($setting);
    }
}
