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
        Schema::create('sport_session_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('staff_id')->nullable(); // Coach
            $table->unsignedBigInteger('facility_id')->nullable();
            
            $table->integer('day_of_week'); // 0 (Sunday) to 6 (Saturday) or 1 (Monday) to 7 (Sunday). Let's use 0-6 where 0 is Sunday.
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_players')->nullable();
            $table->string('gender_allowed', 50)->default('both'); // male, female, both
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_session_templates');
    }
};
