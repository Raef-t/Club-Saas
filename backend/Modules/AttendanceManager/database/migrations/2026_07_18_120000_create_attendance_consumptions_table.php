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
        Schema::create('attendance_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            
            // The generic subscription plan
            $table->unsignedBigInteger('subscription_plan_id')->index();
            
            // The specific player's subscription instance
            $table->unsignedBigInteger('player_subscription_id')->index();
            
            $table->string('status', 50)->default('consumed'); // consumed, rollback, etc.
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_consumptions');
    }
};
