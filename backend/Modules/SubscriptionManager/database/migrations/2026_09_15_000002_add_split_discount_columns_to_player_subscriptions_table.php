<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('player_subscriptions', 'coach_discount_percentage')) {
                $table->decimal('coach_discount_percentage', 8, 2)->default(0)->after('discount_percentage');
            }

            if (!Schema::hasColumn('player_subscriptions', 'branch_discount_percentage')) {
                $table->decimal('branch_discount_percentage', 8, 2)->default(0)->after('coach_discount_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('player_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('player_subscriptions', 'coach_discount_percentage')) {
                $table->dropColumn('coach_discount_percentage');
            }

            if (Schema::hasColumn('player_subscriptions', 'branch_discount_percentage')) {
                $table->dropColumn('branch_discount_percentage');
            }
        });
    }
};
