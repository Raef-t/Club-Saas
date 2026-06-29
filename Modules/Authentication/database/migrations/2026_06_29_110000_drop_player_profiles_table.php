<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('player_profiles');
    }

    public function down(): void
    {
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id')->unique();
            $table->string('qr_code')->nullable();
            $table->string('blood_type')->nullable();
            $table->json('medical_conditions')->nullable();
            $table->json('emergency_contact')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();
        });
    }
};
