<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            if (Schema::hasColumn('branch_settings', 'payroll_start_day')) {
                $table->dropColumn('payroll_start_day');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('branch_settings', 'payroll_start_day')) {
                $table->tinyInteger('payroll_start_day')->nullable()->after('display_mixed_activities');
            }
        });
    }
};
