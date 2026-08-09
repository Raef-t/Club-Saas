<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Change dangerous CASCADE FK constraints to RESTRICT
 * for MemberManager module (members table as parent).
 *
 * PRODUCTION SAFE:
 * - Only FK definitions are changed. Zero data modification.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. members → player_subscriptions ───────────────────────────────
        Schema::table('player_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')
                  ->references('id')->on('members')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

        // ─── 3. members → invoices ────────────────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')
                  ->references('id')->on('members')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

        // ─── 4. members → sports_session_bookings ─────────────────────────────
        Schema::table('sports_session_bookings', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')
                  ->references('id')->on('members')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

        // ─── 5. branches → members ─────────────────────────────────────────────
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

        // ─── 6. branches → invoices ───────────────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('sports_session_bookings', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });

        Schema::table('player_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }
};
