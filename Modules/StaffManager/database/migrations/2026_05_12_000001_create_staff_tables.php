<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Staff table
        Schema::create('staff', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('person_id');
            $table->unsignedBigInteger('branch_id');
            
            $table->enum('role', ['admin', 'receptionist', 'coach', 'cleaner', 'manager'])->default('staff');
            $table->enum('employment_type', ['fixed_salary', 'commission_based', 'hybrid'])->default('fixed_salary');
            
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0); // For activity coaches
            
            $table->string('specialization')->nullable(); // e.g. Yoga, Bodybuilding
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();


            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });

        // 2. Staff Shifts (Schedules)
        Schema::create('staff_shifts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('staff_id');
            
            $table->tinyInteger('day_of_week'); // 0 (Sunday) to 6 (Saturday)
            $table->time('start_time');
            $table->time('end_time');
            
            $table->timestamps();


            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_shifts');
        Schema::dropIfExists('staff');
    }
};
