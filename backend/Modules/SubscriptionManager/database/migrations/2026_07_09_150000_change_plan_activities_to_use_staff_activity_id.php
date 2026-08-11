<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_activities', function (Blueprint $table) {
            if (Schema::hasColumn('plan_activities', 'activity_id')) {
                $table->dropForeign(['activity_id']);
                $table->dropColumn('activity_id');
            }
            if (Schema::hasColumn('plan_activities', 'coach_id')) {
                $table->dropForeign(['coach_id']);
                $table->dropColumn('coach_id');
            }
            
            if (!Schema::hasColumn('plan_activities', 'staff_activity_id')) {
                $table->unsignedBigInteger('staff_activity_id')->nullable()->after('plan_id');
                // The staff_activities table might not have a foreign key set up cross-module, but we'll try to add one.
                $table->foreign('staff_activity_id')->references('id')->on('staff_activities')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plan_activities', function (Blueprint $table) {
            if (Schema::hasColumn('plan_activities', 'staff_activity_id')) {
                $table->dropForeign(['staff_activity_id']);
                $table->dropColumn('staff_activity_id');
            }
            
            if (!Schema::hasColumn('plan_activities', 'activity_id')) {
                $table->unsignedBigInteger('activity_id')->nullable();
                $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('plan_activities', 'coach_id')) {
                $table->unsignedBigInteger('coach_id')->nullable();
                $table->foreign('coach_id')->references('id')->on('staff')->onDelete('set null');
            }
        });
    }
};
