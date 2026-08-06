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
        $tables = [
            'subscription_plans',
            'subscription_plan_activities',
            'offers',
            'offer_subscription_plan',
            'player_subscriptions',
            'player_subscription_items',
            'subscription_freezes',
            'invoices',
            'payments',
            'locker_reservations',
            'player_attendances',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'subscription_plans',
            'subscription_plan_activities',
            'offers',
            'offer_subscription_plan',
            'player_subscriptions',
            'player_subscription_items',
            'subscription_freezes',
            'invoices',
            'payments',
            'locker_reservations',
            'player_attendances',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
