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
        // 1. Player Profiles
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('qr_code')->unique();
            $table->string('blood_type')->nullable();
            $table->json('medical_conditions')->nullable();
            $table->json('emergency_contact')->nullable();
            $table->timestamps();
        });

        // 2. Coach Profiles
        Schema::create('coach_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('specialization');
            $table->text('bio')->nullable();
            $table->integer('experience_years')->default(0);
            $table->timestamps();
        });

        // 3. Staff Profiles
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('job_title');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('coach_profiles');
        Schema::dropIfExists('player_profiles');
    }
};
