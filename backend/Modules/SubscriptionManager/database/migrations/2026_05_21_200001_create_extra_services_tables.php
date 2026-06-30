<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Define extra services (e.g., Locker rental, Towel service)
        Schema::create('extra_services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Or json('name') for translations
            $table->text('description')->nullable();
            $table->decimal('default_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Link services to a player's subscription
        Schema::create('player_subscription_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_subscription_id');
            $table->unsignedBigInteger('extra_service_id');
            $table->decimal('price_charged', 10, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->foreign('player_subscription_id')->references('id')->on('player_subscriptions')->onDelete('cascade');
            $table->foreign('extra_service_id')->references('id')->on('extra_services')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_subscription_services');
        Schema::dropIfExists('extra_services');
    }
};
