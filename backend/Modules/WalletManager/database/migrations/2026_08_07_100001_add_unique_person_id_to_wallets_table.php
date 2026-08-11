<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add unique constraint on wallets.person_id to prevent duplicate wallets.
 *
 * PRODUCTION SAFE:
 * - Checks for duplicate person_id entries before adding the constraint.
 * - If duplicates exist, the constraint is SKIPPED (logged as warning).
 * - No data is deleted or modified.
 * - No API response shape is changed.
 * - WalletService::deposit() already uses firstOrCreate() which benefits
 *   from this constraint (reduces race-condition window).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallets')) {
            return;
        }

        // Check for duplicate person_id before adding unique constraint
        $duplicates = DB::table('wallets')
            ->select('person_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('person_id')
            ->having('cnt', '>', 1)
            ->count();

        if ($duplicates > 0) {
            \Illuminate\Support\Facades\Log::warning(
                "[Migration] Skipped unique constraint on wallets.person_id — {$duplicates} duplicate person_id(s) found. Clean them first."
            );
            return;
        }

        // Check if the index already exists
        $indexExists = collect(Schema::getIndexes('wallets'))
            ->pluck('name')
            ->contains('wallets_person_id_unique');

        if (! $indexExists) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->unique('person_id', 'wallets_person_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('wallets')) {
            return;
        }

        $indexExists = collect(Schema::getIndexes('wallets'))
            ->pluck('name')
            ->contains('wallets_person_id_unique');

        if ($indexExists) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropUnique('wallets_person_id_unique');
            });
        }
    }
};
