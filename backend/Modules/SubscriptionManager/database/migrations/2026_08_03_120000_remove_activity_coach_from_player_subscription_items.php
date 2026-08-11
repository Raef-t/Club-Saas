<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop foreign key for activity_id if present
        try {
            Schema::table('player_subscription_items', function (Blueprint $table) {
                $table->dropForeign(['activity_id']);
            });
        } catch (\Exception $e) {
            // Foreign key might not exist or already dropped
        }

        // 2. Drop columns
        Schema::table('player_subscription_items', function (Blueprint $table) {
            if (Schema::hasColumn('player_subscription_items', 'activity_id')) {
                $table->dropColumn('activity_id');
            }

            if (Schema::hasColumn('player_subscription_items', 'coach_id')) {
                $table->dropColumn('coach_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('player_subscription_items', function (Blueprint $table) {
            if (!Schema::hasColumn('player_subscription_items', 'activity_id')) {
                $table->unsignedBigInteger('activity_id')->nullable()->after('player_subscription_id');
            }

            if (!Schema::hasColumn('player_subscription_items', 'coach_id')) {
                $table->unsignedBigInteger('coach_id')->nullable()->after('activity_id');
            }
        });
    }
};
