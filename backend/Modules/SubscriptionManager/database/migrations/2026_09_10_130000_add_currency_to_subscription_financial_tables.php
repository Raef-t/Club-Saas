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
        // 1. subscription_plans
        if (Schema::hasTable('subscription_plans') && !Schema::hasColumn('subscription_plans', 'currency')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->string('currency', 10)->default('SYP')->after('base_price');
            });
        }

        // 2. player_subscriptions
        if (Schema::hasTable('player_subscriptions') && !Schema::hasColumn('player_subscriptions', 'currency')) {
            Schema::table('player_subscriptions', function (Blueprint $table) {
                $table->string('currency', 10)->default('SYP')->after('total_amount');
            });
        }

        // 3. invoices
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'currency')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('currency', 10)->default('SYP')->after('total');
            });
        }

        // 4. payments
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'currency')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('currency', 10)->default('SYP')->after('amount');
            });
        }

        // 5. subscription_revenue_splits
        if (Schema::hasTable('subscription_revenue_splits') && !Schema::hasColumn('subscription_revenue_splits', 'currency')) {
            Schema::table('subscription_revenue_splits', function (Blueprint $table) {
                $table->string('currency', 10)->default('SYP')->after('total_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscription_plans') && Schema::hasColumn('subscription_plans', 'currency')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        if (Schema::hasTable('player_subscriptions') && Schema::hasColumn('player_subscriptions', 'currency')) {
            Schema::table('player_subscriptions', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'currency')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'currency')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        if (Schema::hasTable('subscription_revenue_splits') && Schema::hasColumn('subscription_revenue_splits', 'currency')) {
            Schema::table('subscription_revenue_splits', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
};
