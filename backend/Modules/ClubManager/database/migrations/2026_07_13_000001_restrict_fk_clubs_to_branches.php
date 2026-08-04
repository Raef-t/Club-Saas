<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Change dangerous CASCADE FK constraints to RESTRICT
 * for ClubManager module (clubs, branches).
 *
 * PRODUCTION SAFE:
 * - We drop the old FK and re-create it with RESTRICT.
 * - No data is touched.
 * - Each change is wrapped in its own Schema::table() call to fail fast.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. clubs → branches ─────────────────────────────────────────────
        // Old: onDelete('cascade')  →  New: RESTRICT (default, no action)
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->foreign('club_id')
                  ->references('id')->on('clubs')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->foreign('club_id')
                  ->references('id')->on('clubs')
                  ->onDelete('cascade');
        });
    }
};
