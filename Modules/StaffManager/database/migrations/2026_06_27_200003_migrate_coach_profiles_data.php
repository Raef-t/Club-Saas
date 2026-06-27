<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrate data from coach_profiles → coach_details
     * and from staff.certificates_held → coach_certifications
     */
    public function up(): void
    {
        // 1. Migrate coach_profiles data into coach_details
        if (Schema::hasTable('coach_profiles')) {
            $coachProfiles = DB::table('coach_profiles')->get();

            foreach ($coachProfiles as $profile) {
                // Find the matching staff record via person_id where role = coach
                $staff = DB::table('staff')
                    ->where('person_id', $profile->person_id)
                    ->where('role', 'coach')
                    ->first();

                if (!$staff) {
                    continue;
                }

                // Skip if coach_details already exists for this staff
                if (DB::table('coach_details')->where('staff_id', $staff->id)->exists()) {
                    continue;
                }

                DB::table('coach_details')->insert([
                    'staff_id'                => $staff->id,
                    'specialization'          => $profile->specialization ?? $staff->specialization ?? null,
                    'bio'                     => $profile->bio ?? null,
                    'experience_years'        => $profile->experience_years ?? 0,
                    'payment_type'            => $profile->payment_type ?? null,
                    'commission_type'         => $profile->commission_type ?? null,
                    'default_commission_rate' => $profile->commission_rate ?? $staff->commission_rate ?? null,
                    'working_hours_per_week'  => $profile->working_hours ?? null,
                    'gym_type'                => $profile->gym_type ?? $staff->gym_type ?? null,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);
            }
        }

        // 2. For any coaches in staff table without a coach_details record, create one
        $coachStaffIds = DB::table('staff')
            ->where('role', 'coach')
            ->pluck('id')
            ->toArray();

        $existingDetailStaffIds = DB::table('coach_details')
            ->pluck('staff_id')
            ->toArray();

        $missingStaffIds = array_diff($coachStaffIds, $existingDetailStaffIds);

        foreach ($missingStaffIds as $staffId) {
            $staff = DB::table('staff')->where('id', $staffId)->first();

            DB::table('coach_details')->insert([
                'staff_id'                => $staffId,
                'specialization'          => $staff->specialization ?? null,
                'default_commission_rate' => $staff->commission_rate ?? null,
                'gym_type'                => $staff->gym_type ?? null,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
        }

        // 3. Migrate staff.certificates_held (JSON) → coach_certifications
        $staffWithCerts = DB::table('staff')
            ->where('role', 'coach')
            ->whereNotNull('certificates_held')
            ->get();

        foreach ($staffWithCerts as $staff) {
            $coachDetail = DB::table('coach_details')
                ->where('staff_id', $staff->id)
                ->first();

            if (!$coachDetail) {
                continue;
            }

            $certs = json_decode($staff->certificates_held, true);
            if (is_array($certs)) {
                foreach ($certs as $certName) {
                    if (!empty($certName)) {
                        DB::table('coach_certifications')->insert([
                            'coach_detail_id' => $coachDetail->id,
                            'name'            => $certName,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Data migration — no rollback needed (source tables still exist during rollback)
        DB::table('coach_certifications')->truncate();
        DB::table('coach_details')->truncate();
    }
};
