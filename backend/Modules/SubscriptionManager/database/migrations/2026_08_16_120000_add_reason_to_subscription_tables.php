<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_plans') && !Schema::hasColumn('subscription_plans', 'reason')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('status');
            });
        }

        if (Schema::hasTable('player_subscriptions') && !Schema::hasColumn('player_subscriptions', 'reason')) {
            Schema::table('player_subscriptions', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subscription_plans') && Schema::hasColumn('subscription_plans', 'reason')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }

        if (Schema::hasTable('player_subscriptions') && Schema::hasColumn('player_subscriptions', 'reason')) {
            Schema::table('player_subscriptions', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
