<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Change dangerous CASCADE FK constraints to RESTRICT
 * for Sports module.
 *
 * PRODUCTION SAFE: Only FK definitions are changed.
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

        // ─── 2. activities → player_subscription_items ───────────────────────
        Schema::table('player_subscription_items', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->foreign('activity_id')
                  ->references('id')->on('activities')
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

        Schema::table('player_subscription_items', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });

        Schema::table('sports_session_bookings', function (Blueprint $table) {
            $table->dropForeign(['sports_session_id']);
            $table->foreign('sports_session_id')->references('id')->on('sports_sessions')->onDelete('cascade');
        });
    }
};
