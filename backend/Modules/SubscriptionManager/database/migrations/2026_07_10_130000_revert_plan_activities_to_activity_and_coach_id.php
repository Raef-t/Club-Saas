<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_activities', function (Blueprint $table) {
            if (Schema::hasColumn('plan_activities', 'staff_activity_id')) {
                $table->dropForeign(['staff_activity_id']);
                $table->dropColumn('staff_activity_id');
            }
            
            if (!Schema::hasColumn('plan_activities', 'activity_id')) {
                $table->unsignedBigInteger('activity_id')->nullable()->after('plan_id');
                $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('plan_activities', 'coach_id')) {
                $table->unsignedBigInteger('coach_id')->nullable()->after('activity_id');
                $table->foreign('coach_id')->references('id')->on('staff')->onDelete('set null');
            }
        });
    }

    public function down(): void
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
                $table->unsignedBigInteger('staff_activity_id')->nullable();
                $table->foreign('staff_activity_id')->references('id')->on('staff_activities')->onDelete('cascade');
            }
        });
    }
};
