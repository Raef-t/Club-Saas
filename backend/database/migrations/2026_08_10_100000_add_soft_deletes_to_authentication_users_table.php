<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('authentication_users', 'deleted_at')) {
            Schema::table('authentication_users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 1. Restore & Fix Super Admin / Admin accounts (person_id = 1 & all admins)
        DB::table('people')
            ->where('id', 1)
            ->orWhere('type', 'admin')
            ->update([
                'type' => 'admin',
                'deleted_at' => null,
            ]);

        DB::table('authentication_users')
            ->where('person_id', 1)
            ->orWhere('role', 'super_admin')
            ->orWhere('role', 'admin')
            ->update([
                'is_active' => true,
                'deleted_at' => null,
            ]);

        // 2. Completely purge all staff records and staff-related data for person_id = 1
        $adminStaffIds = DB::table('staff')->where('person_id', 1)->pluck('id');
        if ($adminStaffIds->isNotEmpty()) {
            DB::table('staff_shifts')->whereIn('staff_id', $adminStaffIds)->delete();
            DB::table('staff_leaves')->whereIn('staff_id', $adminStaffIds)->delete();
            DB::table('staff_contracts')->whereIn('staff_id', $adminStaffIds)->delete();
            DB::table('coach_details')->whereIn('staff_id', $adminStaffIds)->delete();

            $payslipIds = DB::table('payslips')->whereIn('staff_id', $adminStaffIds)->pluck('id');
            if ($payslipIds->isNotEmpty()) {
                DB::table('payslip_adjustments')->whereIn('payslip_id', $payslipIds)->delete();
                DB::table('payslips')->whereIn('staff_id', $adminStaffIds)->delete();
            }

            DB::table('staff_branches')->whereIn('staff_id', $adminStaffIds)->delete();

            if (Schema::hasTable('staff_activities')) {
                DB::table('staff_activities')->whereIn('staff_id', $adminStaffIds)->delete();
            }
            if (Schema::hasTable('staff_commission_rules')) {
                DB::table('staff_commission_rules')->whereIn('staff_id', $adminStaffIds)->delete();
            }

            DB::table('staff')->whereIn('id', $adminStaffIds)->delete();
        }

        // Completely purge any member record for person_id = 1 if exists
        DB::table('members')->where('person_id', 1)->delete();

        // 3. Cleanup existing orphan people records (EXCLUDING admins)
        $trashedPersonIdsFromMembers = DB::table('members')
            ->whereNotNull('deleted_at')
            ->pluck('person_id')
            ->filter();

        $trashedPersonIdsFromStaff = DB::table('staff')
            ->whereNotNull('deleted_at')
            ->pluck('person_id')
            ->filter();

        $candidatePersonIds = $trashedPersonIdsFromMembers->merge($trashedPersonIdsFromStaff)->unique();

        foreach ($candidatePersonIds as $personId) {
            if ($personId == 1) continue;

            $hasActiveMember = DB::table('members')->where('person_id', $personId)->whereNull('deleted_at')->exists();
            $hasActiveStaff = DB::table('staff')->where('person_id', $personId)->whereNull('deleted_at')->exists();

            if (!$hasActiveMember && !$hasActiveStaff) {
                DB::table('people')
                    ->where('id', $personId)
                    ->where('type', '!=', 'admin')
                    ->whereNull('deleted_at')
                    ->update([
                        'deleted_at' => now(),
                    ]);
            }
        }

        // 4. Cleanup existing authentication_users records (EXCLUDING admins)
        $trashedPersonIds = DB::table('people')->whereNotNull('deleted_at')->pluck('id');

        DB::table('authentication_users')
            ->whereNull('deleted_at')
            ->where('role', '!=', 'super_admin')
            ->where('role', '!=', 'admin')
            ->where(function ($query) use ($trashedPersonIds) {
                $query->whereIn('person_id', $trashedPersonIds)
                      ->orWhereNotIn('person_id', DB::table('people')->pluck('id'));
            })
            ->update([
                'deleted_at' => now(),
                'is_active' => false,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('authentication_users', 'deleted_at')) {
            Schema::table('authentication_users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
