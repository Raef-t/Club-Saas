<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1. Expand enum to include all possible statuses (old and new)
        DB::statement("ALTER TABLE player_subscriptions MODIFY COLUMN status ENUM('active', 'expired', 'frozen', 'cancelled', 'finished', 'terminated') DEFAULT 'active'");

        // 2. Migrate existing data
        DB::statement("UPDATE player_subscriptions SET status = 'finished' WHERE status = 'expired'");
        DB::statement("UPDATE player_subscriptions SET status = 'terminated' WHERE status = 'cancelled'");

        // 3. Restrict enum to only the new valid statuses
        DB::statement("ALTER TABLE player_subscriptions MODIFY COLUMN status ENUM('active', 'finished', 'frozen', 'terminated') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1. Expand enum to include all possible statuses (old and new)
        DB::statement("ALTER TABLE player_subscriptions MODIFY COLUMN status ENUM('active', 'expired', 'frozen', 'cancelled', 'finished', 'terminated') DEFAULT 'active'");

        // 2. Revert data
        DB::statement("UPDATE player_subscriptions SET status = 'expired' WHERE status = 'finished'");
        DB::statement("UPDATE player_subscriptions SET status = 'cancelled' WHERE status = 'terminated'");

        // 3. Restrict enum to original statuses
        DB::statement("ALTER TABLE player_subscriptions MODIFY COLUMN status ENUM('active', 'expired', 'frozen', 'cancelled') DEFAULT 'active'");
    }
};
