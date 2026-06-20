<?php

namespace Modules\MemberManager\Services\Me;

use Illuminate\Support\Facades\DB;

class MePhysicalStatsService
{
    /**
     * Update the physical stats (height, weight, dob) of the member.
     *
     * @param mixed $user The authenticated user
     * @param array $validated The validated data array
     * @return void
     * @throws \Exception
     */
    public function updateStats($user, array $validated): void
    {
        $personId = $user->person_id;
        if (!$personId) {
            if (isset($user->person) && $user->person) {
                $personId = $user->person->id;
            } else {
                throw new \Exception(__('Member profile not found.'));
            }
        }

        DB::beginTransaction();
        try {
            // Update dob in people table if provided
            if (isset($validated['dob'])) {
                DB::table('people')
                    ->where('id', $personId)
                    ->update([
                        'dob' => $validated['dob'],
                        'updated_at' => now()
                    ]);
            }

            // Find member
            $member = DB::table('members')
                ->where('person_id', $personId)
                ->first();

            if ($member && (isset($validated['height']) || isset($validated['weight']))) {
                // Calculate BMI
                $bmi = null;
                $height = $validated['height'] ?? null;
                $weight = $validated['weight'] ?? null;

                // Try to get existing latest height/weight if one is missing
                if ($height === null || $weight === null) {
                    $latestMeasurement = DB::table('member_measurements')
                        ->where('member_id', $member->id)
                        ->orderByDesc('measurement_date')
                        ->first();

                    if ($latestMeasurement) {
                        $height = $height ?? $latestMeasurement->height;
                        $weight = $weight ?? $latestMeasurement->weight;
                    }
                }

                if ($height && $weight && $height > 0) {
                    $heightInMeters = $height / 100;
                    $bmi = round($weight / ($heightInMeters * $heightInMeters), 2);
                }

                DB::table('member_measurements')->insert([
                    'member_id' => $member->id,
                    'measurement_date' => now()->toDateString(),
                    'height' => $validated['height'] ?? null,
                    'weight' => $validated['weight'] ?? null,
                    'bmi' => $bmi,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
