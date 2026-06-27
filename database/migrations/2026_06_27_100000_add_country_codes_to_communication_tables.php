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
        Schema::table('people', function (Blueprint $table) {
            $table->string('mobile_1_country_code', 5)->nullable()->after('mobile_1');
            $table->string('mobile_2_country_code', 5)->nullable()->after('mobile_2');
            $table->string('emergency_contact_country_code', 5)->nullable()->after('emergency_contact_phone');
        });

        Schema::table('person_contacts', function (Blueprint $table) {
            $table->string('country_code', 5)->nullable()->after('phone_number');
        });

        Schema::table('member_health_profiles', function (Blueprint $table) {
            $table->string('emergency_contact_country_code', 5)->nullable()->after('emergency_contact_phone');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('country_code', 5)->nullable()->after('phone');
        });

        Schema::table('acc_counterparties', function (Blueprint $table) {
            $table->string('country_code', 5)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['mobile_1_country_code', 'mobile_2_country_code', 'emergency_contact_country_code']);
        });

        Schema::table('person_contacts', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });

        Schema::table('member_health_profiles', function (Blueprint $table) {
            $table->dropColumn('emergency_contact_country_code');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });

        Schema::table('acc_counterparties', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });
    }
};
