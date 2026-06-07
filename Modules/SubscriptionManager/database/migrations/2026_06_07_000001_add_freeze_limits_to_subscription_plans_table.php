<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->integer('max_freeze_count')->nullable()->after('session_count');
            $table->integer('max_freeze_days')->nullable()->after('max_freeze_count');
        });

        Schema::table('subscription_freezes', function (Blueprint $table) {
            $table->date('actual_end_date')->nullable()->after('freeze_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['max_freeze_count', 'max_freeze_days']);
        });

        Schema::table('subscription_freezes', function (Blueprint $table) {
            $table->dropColumn('actual_end_date');
        });
    }
};
