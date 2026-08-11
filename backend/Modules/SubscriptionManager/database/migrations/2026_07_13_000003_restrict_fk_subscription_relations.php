<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Change dangerous CASCADE FK constraints to RESTRICT
 * for SubscriptionManager module.
 *
 * PRODUCTION SAFE: Only FK definitions are changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. subscription_plans → player_subscriptions ────────────────────
        Schema::table('player_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->foreign('plan_id')
                  ->references('id')->on('subscription_plans')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

        // ─── 2. invoices → payments ───────────────────────────────────────────
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->foreign('invoice_id')
                  ->references('id')->on('invoices')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

        // ─── 3. extra_services → player_subscription_services ────────────────
        Schema::table('player_subscription_services', function (Blueprint $table) {
            $table->dropForeign(['extra_service_id']);
            $table->foreign('extra_service_id')
                  ->references('id')->on('extra_services')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

        // ─── 4. branches → subscription_plans ────────────────────────────────
        if (Schema::hasColumn('subscription_plans', 'branch_id')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->foreign('branch_id')
                      ->references('id')->on('branches')
                      ->onUpdate('cascade')
                      ->onDelete('restrict');
            });
        }

        // ─── 5. branches → extra_services ────────────────────────────────────
        if (Schema::hasColumn('extra_services', 'branch_id')) {
            Schema::table('extra_services', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->foreign('branch_id')
                      ->references('id')->on('branches')
                      ->onUpdate('cascade')
                      ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::table('player_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->foreign('plan_id')->references('id')->on('subscription_plans')->onDelete('cascade');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });

        Schema::table('player_subscription_services', function (Blueprint $table) {
            $table->dropForeign(['extra_service_id']);
            $table->foreign('extra_service_id')->references('id')->on('extra_services')->onDelete('cascade');
        });

        if (Schema::hasColumn('subscription_plans', 'branch_id')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            });
        }

        if (Schema::hasColumn('extra_services', 'branch_id')) {
            Schema::table('extra_services', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            });
        }
    }
};
