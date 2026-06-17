<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1\Me;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\MemberManager\Http\Requests\Api\V1\Me\UpdatePreferencesRequest;

class MePreferencesController extends BaseController
{
    /**
     * Get current preferences.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $this->resolvePlayerProfile($user);

        return $this->successResponse([
            'language' => $profile->preferences['language'] ?? 'ar',
            'notifications_enabled' => $profile->preferences['notifications_enabled'] ?? true,
        ], __('Preferences retrieved successfully'));
    }

    /**
     * Update member preferences (language, notifications).
     */
    public function update(UpdatePreferencesRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $personId = $this->resolvePersonId($user);

        if (!$personId) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        $profile = DB::table('player_profiles')
            ->where('person_id', $personId)
            ->first();

        $existingPreferences = $profile ? json_decode($profile->preferences ?? '{}', true) : [];
        $mergedPreferences = array_merge($existingPreferences, $validated);

        if ($profile) {
            DB::table('player_profiles')
                ->where('person_id', $personId)
                ->update([
                    'preferences' => json_encode($mergedPreferences),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('player_profiles')->insert([
                'person_id' => $personId,
                'preferences' => json_encode($mergedPreferences),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->successResponse(null, __('Preferences updated successfully'));
    }

    /**
     * Resolve person_id from the authenticated user.
     */
    protected function resolvePersonId($user): ?int
    {
        if ($user instanceof \Modules\MemberManager\Models\Member) {
            return $user->person_id;
        }

        return $user->person_id ?? null;
    }

    /**
     * Resolve the player_profiles record.
     */
    protected function resolvePlayerProfile($user): object
    {
        $personId = $this->resolvePersonId($user);

        if ($personId) {
            $profile = DB::table('player_profiles')
                ->where('person_id', $personId)
                ->first();

            if ($profile) {
                $profile->preferences = json_decode($profile->preferences ?? '{}', true);
                return $profile;
            }
        }

        return (object) ['preferences' => []];
    }
}
