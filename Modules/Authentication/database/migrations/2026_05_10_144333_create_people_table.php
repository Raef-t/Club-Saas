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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('full_name');
            $table->string('gender', 10)->nullable();
            $table->date('dob')->nullable();
            $table->string('national_id', 50)->nullable();
            $table->string('social_status', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('mobile_1', 20);
            $table->string('mobile_2', 20)->nullable();
            $table->string('landline', 20)->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('chronic_diseases')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
