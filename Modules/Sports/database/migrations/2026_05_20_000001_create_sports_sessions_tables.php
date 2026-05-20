<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff Activities (Mapping to DBML's coach_activities)
        Schema::create('staff_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('activity_id');
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });

        // Staff Commission Rules
        Schema::create('staff_commission_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('activity_id');
            $table->string('calculation_type', 50); // percentage, fixed_amount
            $table->decimal('rate_value', 8, 2);
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });

        // Sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('staff_id')->nullable(); // Coach
            $table->unsignedBigInteger('facility_id')->nullable();
            
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('max_players')->nullable();
            $table->string('status', 50)->default('scheduled');
            $table->integer('booked_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('staff_commission_rules');
        Schema::dropIfExists('staff_activities');
    }
};
