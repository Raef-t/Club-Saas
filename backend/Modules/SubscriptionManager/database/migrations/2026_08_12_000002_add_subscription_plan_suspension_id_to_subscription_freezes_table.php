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
        Schema::table('subscription_freezes', function (Blueprint $table) {
            $table->foreignId('subscription_plan_suspension_id')
                ->nullable()
                ->after('player_subscription_id')
                ->constrained('subscription_plan_suspensions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_freezes', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_suspension_id']);
            $table->dropColumn('subscription_plan_suspension_id');
        });
    }
};
