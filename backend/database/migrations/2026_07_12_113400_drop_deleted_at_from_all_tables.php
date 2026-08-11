<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that previously used soft deletes.
     */
    private array $tables = [
        'clubs',
        'branches',
        'facilities',
        'lockers',
        'members',
        'staff',
        'subscription_plans',
        'player_subscriptions',
        'offers',
        'wallets',
        'wallet_transactions',
        'sports_sessions',
        'sport_session_templates',
        'sport_session_bookings',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropSoftDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }
    }
};
