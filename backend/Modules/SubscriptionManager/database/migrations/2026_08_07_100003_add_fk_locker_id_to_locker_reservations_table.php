<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add a proper foreign key constraint on locker_reservations.locker_id.
 *
 * The original migration (2026_07_14_000002) intentionally skipped this FK
 * with the comment "skipping to avoid coupling". This migration adds it
 * safely with a RESTRICT delete rule so no data is ever silently lost.
 *
 * PRODUCTION SAFE:
 * - Checks for orphaned locker_id references before adding the FK.
 * - If orphaned records exist, the FK is SKIPPED (logged as warning).
 * - Uses onDelete('restrict') — deleting a locker with reservations will
 *   raise a DB error (intentional safeguard, same as other modules).
 * - No API response shape is changed.
 * - No data is deleted or modified.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('locker_reservations') || ! Schema::hasTable('lockers')) {
            return;
        }

        if (! Schema::hasColumn('locker_reservations', 'locker_id')) {
            return;
        }

        // Check for orphaned locker_id values
        $orphans = DB::table('locker_reservations')
            ->whereNotNull('locker_id')
            ->whereNotIn(
                'locker_id',
                DB::table('lockers')->pluck('id')
            )
            ->count();

        if ($orphans > 0) {
            \Illuminate\Support\Facades\Log::warning(
                "[Migration] Skipped FK on locker_reservations.locker_id — {$orphans} orphaned record(s) found. Clean them first."
            );
            return;
        }

        $indexName = 'lr_locker_id_fk';
        $indexExists = collect(Schema::getIndexes('locker_reservations'))
            ->pluck('name')
            ->contains($indexName);

        if (! $indexExists) {
            Schema::table('locker_reservations', function (Blueprint $table) use ($indexName) {
                $table->foreign('locker_id', $indexName)
                      ->references('id')
                      ->on('lockers')
                      ->onUpdate('cascade')
                      ->onDelete('restrict'); // Prevent deleting a locker that has reservations
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('locker_reservations')) {
            return;
        }

        $indexName = 'lr_locker_id_fk';
        $indexExists = collect(Schema::getIndexes('locker_reservations'))
            ->pluck('name')
            ->contains($indexName);

        if ($indexExists) {
            Schema::table('locker_reservations', function (Blueprint $table) use ($indexName) {
                $table->dropForeign($indexName);
            });
        }
    }
};
