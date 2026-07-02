<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds three columns to the attendances table to support the
     * reception check-in workflow:
     *  - recorded_by_staff_id : the logged-in staff who registered the attendance
     *  - locker_id            : the locker key assigned to the player during this visit
     *  - locker_holder_name   : name of the friend when the key is transferred (not returned)
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // The staff member (receptionist) who performed the check-in
            $table->unsignedBigInteger('recorded_by_staff_id')->nullable()->after('branch_id');

            // The locker assigned to the player for this visit
            $table->unsignedBigInteger('locker_id')->nullable()->after('recorded_by_staff_id');

            // Name of the person holding the locker key when transferred to a friend
            $table->string('locker_holder_name')->nullable()->after('locker_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['recorded_by_staff_id', 'locker_id', 'locker_holder_name']);
        });
    }
};
