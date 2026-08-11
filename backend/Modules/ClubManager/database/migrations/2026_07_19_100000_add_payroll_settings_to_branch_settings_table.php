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
            $table->tinyInteger('payroll_start_day')->nullable()->after('display_mixed_activities');
            $table->tinyInteger('payroll_end_day')->nullable()->after('payroll_start_day');
            $table->boolean('include_terminated_subscriptions')->default(false)->after('payroll_end_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn([
                'payroll_start_day',
                'payroll_end_day',
                'include_terminated_subscriptions',
            ]);
        });
    }
};
