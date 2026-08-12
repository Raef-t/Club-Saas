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
        if (!Schema::hasTable('locker_reservations') || !Schema::hasTable('lockers')) {
            return;
        }

        // 1. Repair specific active locker reservation ID 19 or any active reservation where staff_id is NULL for lockers with status 'with_coach' or 'with_staff'
        $nullReservations = DB::table('locker_reservations as lr')
            ->join('lockers as l', 'l.id', '=', 'lr.locker_id')
            ->whereIn('l.status', ['with_coach', 'with_staff'])
            ->where('lr.status', 'active')
            ->whereNull('lr.staff_id')
            ->select('lr.id as reservation_id', 'l.id as locker_id', 'l.status as locker_status')
            ->get();

        foreach ($nullReservations as $res) {
            // Check if there was a previous expired reservation for this locker to get the previous staff_id
            $prevStaffId = DB::table('locker_reservations')
                ->where('locker_id', $res->locker_id)
                ->whereNotNull('staff_id')
                ->latest('id')
                ->value('staff_id');

            $staffIdToAssign = $prevStaffId;

            if (!$staffIdToAssign) {
                // Find a staff/coach in staff table with active status or role 'coach'
                $roleFilter = $res->locker_status === 'with_coach' ? 'coach' : 'staff';
                $staff = DB::table('staff')
                    ->where('role', $roleFilter)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$staff && $res->locker_status === 'with_coach') {
                    $staff = DB::table('staff')
                        ->join('coach_details', 'staff.id', '=', 'coach_details.staff_id')
                        ->whereNull('staff.deleted_at')
                        ->select('staff.id')
                        ->first();
                }

                if (!$staff) {
                    $staff = DB::table('staff')->whereNull('deleted_at')->first();
                }

                $staffIdToAssign = $staff?->id;
            }

            if ($staffIdToAssign) {
                DB::table('locker_reservations')
                    ->where('id', $res->reservation_id)
                    ->update(['staff_id' => $staffIdToAssign]);
            }
        }

        // 2. Repair reservations where staff_id is storing person_id instead of staff.id
        $personIdReservations = DB::table('locker_reservations as lr')
            ->whereNotNull('lr.staff_id')
            ->select('lr.id as reservation_id', 'lr.staff_id')
            ->get();

        foreach ($personIdReservations as $res) {
            $staffById = DB::table('staff')->where('id', $res->staff_id)->first();
            if (!$staffById) {
                $staffByPerson = DB::table('staff')->where('person_id', $res->staff_id)->first();
                if ($staffByPerson) {
                    DB::table('locker_reservations')
                        ->where('id', $res->reservation_id)
                        ->update(['staff_id' => $staffByPerson->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive down action needed
    }
};
