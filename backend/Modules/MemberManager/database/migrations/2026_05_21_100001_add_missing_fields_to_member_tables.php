<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_health_profiles', function (Blueprint $table) {
            $table->string('sport_goal', 100)->nullable()->after('emergency_contact_phone');
            $table->string('fitness_level', 50)->nullable()->after('sport_goal');
        });

        Schema::table('member_measurements', function (Blueprint $table) {
            $table->string('activity_level', 50)->nullable()->after('waist_circumference');
            $table->decimal('bmi', 5, 2)->nullable()->after('activity_level');
        });
    }

    public function down(): void
    {
        Schema::table('member_health_profiles', function (Blueprint $table) {
            $table->dropColumn(['sport_goal', 'fitness_level']);
        });

        Schema::table('member_measurements', function (Blueprint $table) {
            $table->dropColumn(['activity_level', 'bmi']);
        });
    }
};
