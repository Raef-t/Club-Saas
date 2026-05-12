<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('plan_id');
            
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            $table->enum('status', ['active', 'expired', 'frozen', 'cancelled'])->default('active');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->integer('remaining_sessions')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('subscription_plans')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_subscriptions');
    }
};
