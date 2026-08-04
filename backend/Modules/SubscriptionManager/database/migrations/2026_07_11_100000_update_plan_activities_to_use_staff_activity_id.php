<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add staff_activity_id column
        Schema::table('plan_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_activities', 'staff_activity_id')) {
                $table->unsignedBigInteger('staff_activity_id')->nullable()->after('plan_id');
                $table->foreign('staff_activity_id')->references('id')->on('staff_activities')->onDelete('cascade');
            }
        });

        // 2. Migrate existing data
        $planActivities = DB::table('plan_activities')->get();
        foreach ($planActivities as $planActivity) {
            if (isset($planActivity->activity_id)) {
                $coachId = $planActivity->coach_id ?? null;
                
                if ($coachId) {
                    $staffActivity = DB::table('staff_activities')
                        ->where('activity_id', $planActivity->activity_id)
                        ->where('staff_id', $coachId)
                        ->first();
                        
                    if (!$staffActivity) {
                        $staffActivityId = DB::table('staff_activities')->insertGetId([
                            'activity_id' => $planActivity->activity_id,
                            'staff_id' => $coachId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $staffActivityId = $staffActivity->id;
                    }
                } else {
                    $staffActivity = DB::table('staff_activities')
                        ->where('activity_id', $planActivity->activity_id)
                        ->whereNull('staff_id')
                        ->first();
                        
                    if (!$staffActivity) {
                        $staffActivityId = DB::table('staff_activities')->insertGetId([
                            'activity_id' => $planActivity->activity_id,
                            'staff_id' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $staffActivityId = $staffActivity->id;
                    }
                }

                DB::table('plan_activities')
                    ->where('id', $planActivity->id)
                    ->update(['staff_activity_id' => $staffActivityId]);
            }
        }

        // 3. Drop old columns
        Schema::table('plan_activities', function (Blueprint $table) {
            if (Schema::hasColumn('plan_activities', 'activity_id')) {
                $table->dropForeign(['activity_id']);
                $table->dropColumn('activity_id');
            }
            if (Schema::hasColumn('plan_activities', 'coach_id')) {
                $table->dropForeign(['coach_id']);
                $table->dropColumn('coach_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plan_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_activities', 'activity_id')) {
                $table->unsignedBigInteger('activity_id')->nullable()->after('plan_id');
                $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('plan_activities', 'coach_id')) {
                $table->unsignedBigInteger('coach_id')->nullable()->after('activity_id');
                $table->foreign('coach_id')->references('id')->on('staff')->onDelete('set null');
            }
        });

        // Migrate data back
        $planActivities = DB::table('plan_activities')->get();
        foreach ($planActivities as $planActivity) {
            if (isset($planActivity->staff_activity_id)) {
                $staffActivity = DB::table('staff_activities')
                    ->where('id', $planActivity->staff_activity_id)
                    ->first();
                
                if ($staffActivity) {
                    DB::table('plan_activities')
                        ->where('id', $planActivity->id)
                        ->update([
                            'activity_id' => $staffActivity->activity_id,
                            'coach_id' => $staffActivity->staff_id,
                        ]);
                }
            }
        }

        Schema::table('plan_activities', function (Blueprint $table) {
            if (Schema::hasColumn('plan_activities', 'staff_activity_id')) {
                $table->dropForeign(['staff_activity_id']);
                $table->dropColumn('staff_activity_id');
            }
        });
    }
};
