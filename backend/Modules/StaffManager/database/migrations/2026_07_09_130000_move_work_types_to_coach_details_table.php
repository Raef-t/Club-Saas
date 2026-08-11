<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove work_types from staff
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'work_types')) {
                $table->dropColumn('work_types');
            }
        });

        // Add work_types to coach_details
        Schema::table('coach_details', function (Blueprint $table) {
            $table->json('work_types')->nullable()->after('gym_type');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->json('work_types')->nullable();
        });

        Schema::table('coach_details', function (Blueprint $table) {
            $table->dropColumn('work_types');
        });
    }
};
