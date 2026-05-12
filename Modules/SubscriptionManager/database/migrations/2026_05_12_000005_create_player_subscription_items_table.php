<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('player_subscription_id');
            $table->unsignedBigInteger('activity_id');
            
            $table->integer('sessions_allocated')->nullable();
            $table->integer('sessions_consumed')->default(0);
            $table->boolean('is_unlimited')->default(false);
            
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('player_subscription_id')->references('id')->on('player_subscriptions')->onDelete('cascade');
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_subscription_items');
    }
};
