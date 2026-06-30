<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('player_subscription_services')) {
            Schema::table('player_subscription_services', function (Blueprint $table) {
                $table->unsignedBigInteger('locker_id')->nullable()->after('extra_service_id');
                // References lockers.id in ClubManager module.
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('player_subscription_services') && Schema::hasColumn('player_subscription_services', 'locker_id')) {
            Schema::table('player_subscription_services', function (Blueprint $table) {
                $table->dropColumn('locker_id');
            });
        }
    }
};
