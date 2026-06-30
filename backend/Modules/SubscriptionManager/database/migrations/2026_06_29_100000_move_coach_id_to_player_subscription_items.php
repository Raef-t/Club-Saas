<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('player_subscriptions', 'coach_id')) {
                $table->dropColumn('coach_id');
            }
        });

        Schema::table('player_subscription_items', function (Blueprint $table) {
            if (!Schema::hasColumn('player_subscription_items', 'coach_id')) {
                $table->unsignedBigInteger('coach_id')->nullable()->after('activity_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('player_subscription_items', function (Blueprint $table) {
            if (Schema::hasColumn('player_subscription_items', 'coach_id')) {
                $table->dropColumn('coach_id');
            }
        });

        Schema::table('player_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('player_subscriptions', 'coach_id')) {
                $table->unsignedBigInteger('coach_id')->nullable()->after('member_id');
            }
        });
    }
};
