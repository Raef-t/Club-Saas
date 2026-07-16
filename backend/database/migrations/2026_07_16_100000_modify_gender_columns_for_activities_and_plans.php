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
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'gender_allowed')) {
                $table->dropColumn('gender_allowed');
            }
        });

        Schema::table('sport_session_templates', function (Blueprint $table) {
            if (Schema::hasColumn('sport_session_templates', 'gender_allowed')) {
                $table->dropColumn('gender_allowed');
            }
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'gender_restriction')) {
                $table->string('gender_restriction', 50)->default('mixed')->after('type'); // male, female, mixed
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (!Schema::hasColumn('activities', 'gender_allowed')) {
                $table->string('gender_allowed')->default('mixed');
            }
        });

        Schema::table('sport_session_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('sport_session_templates', 'gender_allowed')) {
                $table->string('gender_allowed', 50)->default('mixed');
            }
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_plans', 'gender_restriction')) {
                $table->dropColumn('gender_restriction');
            }
        });
    }
};
