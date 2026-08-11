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
        Schema::table('player_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('player_subscriptions', 'subscription_number')) {
                try {
                    $table->dropUnique('player_subscriptions_subscription_number_unique');
                } catch (\Throwable $e) {
                    // Ignore if unique index doesn't exist
                }
                $table->dropColumn('subscription_number');
            }
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'subscription_number')) {
                $table->string('subscription_number')->nullable()->unique()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('subscription_number');
        });

        Schema::table('player_subscriptions', function (Blueprint $table) {
            $table->string('subscription_number')->nullable()->unique()->after('id');
        });
    }
};
