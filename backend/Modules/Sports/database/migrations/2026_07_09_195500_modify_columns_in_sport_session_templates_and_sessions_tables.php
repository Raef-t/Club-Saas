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
        Schema::table('sport_session_templates', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['activity_id']);
            $table->dropForeign(['staff_id']);
            
            $table->dropColumn(['branch_id', 'activity_id', 'staff_id']);
            
            $table->unsignedBigInteger('plan_id')->after('id')->nullable();
            
            // Assuming subscription_plans table exists in another module
            // $table->foreign('plan_id')->references('id')->on('subscription_plans')->onDelete('cascade');
        });

        Schema::table('sports_sessions', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['activity_id']);
            $table->dropForeign(['staff_id']);
            
            $table->dropColumn(['branch_id', 'activity_id', 'staff_id']);
            
            $table->unsignedBigInteger('plan_id')->after('id')->nullable();
            
            // $table->foreign('plan_id')->references('id')->on('subscription_plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sport_session_templates', function (Blueprint $table) {
            $table->dropColumn('plan_id');
            
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('activity_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
        });

        Schema::table('sports_sessions', function (Blueprint $table) {
            $table->dropColumn('plan_id');
            
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('activity_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
        });
    }
};
