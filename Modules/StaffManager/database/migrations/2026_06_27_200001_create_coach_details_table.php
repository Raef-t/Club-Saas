<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id')->unique(); // 1:1 with staff

            $table->string('specialization')->nullable();     // e.g. Yoga, Bodybuilding, CrossFit
            $table->text('bio')->nullable();
            $table->integer('experience_years')->default(0);
            $table->string('payment_type', 50)->nullable();   // per_session, monthly, etc.
            $table->string('commission_type', 50)->nullable(); // percentage, fixed_amount
            $table->decimal('default_commission_rate', 5, 2)->nullable();
            $table->decimal('working_hours_per_week', 5, 2)->nullable();
            $table->string('gym_type', 20)->nullable();       // male, female, mixed

            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_details');
    }
};
