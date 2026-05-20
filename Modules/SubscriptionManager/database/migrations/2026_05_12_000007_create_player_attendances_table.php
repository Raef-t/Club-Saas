<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_attendances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('player_subscription_id')->nullable();
            $table->unsignedBigInteger('activity_id')->nullable();
            
            $table->dateTime('check_in');
            $table->dateTime('check_out')->nullable();
            $table->integer('duration_minutes')->nullable();
            
            $table->timestamps();


            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('player_subscription_id')->references('id')->on('player_subscriptions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_attendances');
    }
};
