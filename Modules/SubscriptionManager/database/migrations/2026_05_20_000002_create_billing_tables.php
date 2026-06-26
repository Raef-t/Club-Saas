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
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id');
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('player_subscription_id')->nullable();
                $table->decimal('total', 12, 2)->default(0);
                $table->string('status', 50)->default('unpaid');
                $table->timestamps();

                $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                $table->foreign('player_subscription_id')->references('id')->on('player_subscriptions')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('safe_id')->nullable(); // linked to acc_safes in the new system
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('payment_method', 50);
                $table->string('status', 50)->default('completed');
                $table->timestamps();

                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            });
        } else {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'safe_id')) {
                    $table->unsignedBigInteger('safe_id')->nullable()->after('invoice_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
    }
};
