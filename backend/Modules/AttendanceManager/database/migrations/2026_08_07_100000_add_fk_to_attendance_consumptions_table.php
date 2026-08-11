<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add proper foreign key constraints to attendance_consumptions.
 *
 * PRODUCTION SAFE:
 * - Checks for orphaned records before adding each FK.
 * - If orphaned data exists, the FK is SKIPPED (not added) to avoid
 *   breaking a running production environment.
 * - No data is deleted or modified.
 * - No API response shape is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. FK: player_subscription_id → player_subscriptions ────────────
        if (Schema::hasColumn('attendance_consumptions', 'player_subscription_id')
            && Schema::hasTable('player_subscriptions')
        ) {
            $orphans = DB::table('attendance_consumptions')
                ->whereNotNull('player_subscription_id')
                ->whereNotIn(
                    'player_subscription_id',
                    DB::table('player_subscriptions')->pluck('id')
                )
                ->count();

            if ($orphans === 0) {
                Schema::table('attendance_consumptions', function (Blueprint $table) {
                    // Check the index doesn't already exist
                    $indexes = collect(Schema::getIndexes('attendance_consumptions'))
                        ->pluck('name');

                    if (! $indexes->contains('ac_player_subscription_id_fk')) {
                        $table->foreign('player_subscription_id', 'ac_player_subscription_id_fk')
                              ->references('id')
                              ->on('player_subscriptions')
                              ->onUpdate('cascade')
                              ->onDelete('cascade');
                    }
                });
            } else {
                // Log warning but don't fail the migration
                \Illuminate\Support\Facades\Log::warning(
                    "[Migration] Skipped FK on attendance_consumptions.player_subscription_id — {$orphans} orphaned record(s) found."
                );
            }
        }

        // ── 2. FK: subscription_plan_id → subscription_plans ────────────────
        if (Schema::hasColumn('attendance_consumptions', 'subscription_plan_id')
            && Schema::hasTable('subscription_plans')
        ) {
            $orphans = DB::table('attendance_consumptions')
                ->whereNotNull('subscription_plan_id')
                ->whereNotIn(
                    'subscription_plan_id',
                    DB::table('subscription_plans')->pluck('id')
                )
                ->count();

            if ($orphans === 0) {
                Schema::table('attendance_consumptions', function (Blueprint $table) {
                    $indexes = collect(Schema::getIndexes('attendance_consumptions'))
                        ->pluck('name');

                    if (! $indexes->contains('ac_subscription_plan_id_fk')) {
                        $table->foreign('subscription_plan_id', 'ac_subscription_plan_id_fk')
                              ->references('id')
                              ->on('subscription_plans')
                              ->onUpdate('cascade')
                              ->onDelete('cascade');
                    }
                });
            } else {
                \Illuminate\Support\Facades\Log::warning(
                    "[Migration] Skipped FK on attendance_consumptions.subscription_plan_id — {$orphans} orphaned record(s) found."
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('attendance_consumptions', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('attendance_consumptions'))->pluck('name');

            if ($indexes->contains('ac_player_subscription_id_fk')) {
                $table->dropForeign('ac_player_subscription_id_fk');
            }

            if ($indexes->contains('ac_subscription_plan_id_fk')) {
                $table->dropForeign('ac_subscription_plan_id_fk');
            }
        });
    }
};
