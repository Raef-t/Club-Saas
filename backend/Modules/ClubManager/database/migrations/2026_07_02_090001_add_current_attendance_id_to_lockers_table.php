<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add current_attendance_id to lockers to track which attendance record
     * is currently holding this locker key. NULL means the locker is free.
     */
    public function up(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            // Points to the open attendance record that holds this locker key.
            // When the key is returned or transferred this is set back to NULL.
            $table->unsignedBigInteger('current_attendance_id')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->dropColumn('current_attendance_id');
        });
    }
};
