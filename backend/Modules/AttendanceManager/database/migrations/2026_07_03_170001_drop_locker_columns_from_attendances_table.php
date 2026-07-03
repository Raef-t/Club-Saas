<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove all locker-related columns from the attendances table.
 *
 * Locker state is now fully managed in the lockers table itself
 * (holder_id, holder_type, holder_name, assigned_at, status).
 * The attendances table no longer needs to know anything about lockers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['locker_id', 'locker_holder_name']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('locker_id')->nullable()->after('recorded_by_staff_id');
            $table->string('locker_holder_name')->nullable()->after('locker_id');
        });
    }
};
