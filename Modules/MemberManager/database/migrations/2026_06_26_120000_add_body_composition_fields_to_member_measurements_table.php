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
        Schema::table('member_measurements', function (Blueprint $table) {
            if (!Schema::hasColumn('member_measurements', 'neck_circumference')) {
                $table->decimal('neck_circumference', 5, 2)->nullable()->after('waist_circumference');
            }
            if (!Schema::hasColumn('member_measurements', 'shoulder_circumference')) {
                $table->decimal('shoulder_circumference', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'right_bicep')) {
                $table->decimal('right_bicep', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'left_bicep')) {
                $table->decimal('left_bicep', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'hip_circumference')) {
                $table->decimal('hip_circumference', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'right_thigh_mid')) {
                $table->decimal('right_thigh_mid', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'left_thigh')) {
                $table->decimal('left_thigh', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'right_calf')) {
                $table->decimal('right_calf', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'left_calf')) {
                $table->decimal('left_calf', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'fat_free_mass_percentage')) {
                $table->decimal('fat_free_mass_percentage', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'body_water_percentage')) {
                $table->decimal('body_water_percentage', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'resting_metabolic_rate')) {
                $table->decimal('resting_metabolic_rate', 6, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'total_daily_energy_expenditure')) {
                $table->decimal('total_daily_energy_expenditure', 6, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'physical_activity_level')) {
                $table->enum('physical_activity_level', ['sedentary', 'light', 'medium', 'high', 'extreme'])->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'buttocks_circumference')) {
                $table->decimal('buttocks_circumference', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'above_right_knee')) {
                $table->decimal('above_right_knee', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('member_measurements', 'above_left_knee')) {
                $table->decimal('above_left_knee', 5, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_measurements', function (Blueprint $table) {
            $table->dropColumn([
                'neck_circumference',
                'shoulder_circumference',
                'right_bicep',
                'left_bicep',
                'hip_circumference',
                'right_thigh_mid',
                'left_thigh',
                'right_calf',
                'left_calf',
                'fat_free_mass_percentage',
                'body_water_percentage',
                'resting_metabolic_rate',
                'total_daily_energy_expenditure',
                'physical_activity_level',
                'buttocks_circumference',
                'above_right_knee',
                'above_left_knee',
            ]);
        });
    }
};
