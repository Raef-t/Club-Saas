<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Change dangerous CASCADE FK constraints to RESTRICT
 * for Sports module.
 *
 * PRODUCTION SAFE: Only FK definitions are changed.
 *
 * NOTE: The player_subscription_items block was removed because
 * activity_id was dropped from that table in migration
 * 2026_08_03_120000_remove_activity_coach_from_player_subscription_items.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. activities → plan_activities ─────────────────────────────────
        // (plan_activities uses staff_activity_id now, not direct activity_id)
        // The staff_activities → plan_activities FK is the relevant one
        Schema::table('plan_activities', function (Blueprint $table) {
            $table->dropForeign(['staff_activity_id']);
            $table->foreign('staff_activity_id')
                  ->references('id')->on('staff_activities')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

        // ─── 4. sports_sessions → sports_session_bookings ────────────────────
        Schema::table('sports_session_bookings', function (Blueprint $table) {
            $table->dropForeign(['sports_session_id']);
            $table->foreign('sports_session_id')
                  ->references('id')->on('sports_sessions')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('plan_activities', function (Blueprint $table) {
            $table->dropForeign(['staff_activity_id']);
            $table->foreign('staff_activity_id')->references('id')->on('staff_activities')->onDelete('cascade');
        });

        Schema::table('sports_session_bookings', function (Blueprint $table) {
            $table->dropForeign(['sports_session_id']);
            $table->foreign('sports_session_id')->references('id')->on('sports_sessions')->onDelete('cascade');
        });
    }
};
