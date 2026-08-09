<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update staff table
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('work_type');
            $table->json('work_types')->nullable()->after('shift_type');
        });

        // 2. Update coach_details table
        Schema::table('coach_details', function (Blueprint $table) {
            $table->dropColumn(['specialization', 'working_hours_per_week']);
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('work_types');
            $table->string('work_type', 50)->nullable()->after('shift_type');
        });

        Schema::table('coach_details', function (Blueprint $table) {
            $table->string('specialization')->nullable();
            $table->decimal('working_hours_per_week', 5, 2)->nullable();
        });
    }
};
