<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('full_name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('status', 50)->default('new');
            $table->string('source', 100)->nullable();
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->string('pipeline_stage', 50)->nullable();
            $table->timestamps();
            
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('assigned_to_user_id')->references('id')->on('authentication_users')->onDelete('set null');
        });

        Schema::create('lead_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('interaction_type', 50);
            $table->text('notes')->nullable();
            $table->dateTime('next_follow_up')->nullable();
            $table->timestamps();
            
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('authentication_users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_interactions');
        Schema::dropIfExists('leads');
    }
};
