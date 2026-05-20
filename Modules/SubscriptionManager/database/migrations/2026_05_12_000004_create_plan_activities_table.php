<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_activities', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('activity_id'); // From Sports Module
            
            $table->integer('sessions_count')->nullable(); // null means unlimited
            $table->boolean('is_unlimited')->default(false);
            
            $table->timestamps();


            $table->foreign('plan_id')->references('id')->on('subscription_plans')->onDelete('cascade');
            // Note: activity_id is cross-module, we don't always add hard foreign key in multi-db setup, 
            // but in modular monolith same db, it's fine.
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_activities');
    }
};
