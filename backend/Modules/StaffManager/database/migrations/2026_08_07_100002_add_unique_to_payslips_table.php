<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add unique constraint on payslips(payroll_run_id, staff_id) to prevent
 * duplicate payslips for the same staff in the same payroll run.
 *
 * PRODUCTION SAFE:
 * - Checks for existing duplicates before adding the constraint.
 * - If duplicates exist, the constraint is SKIPPED (logged as warning).
 * - No data is deleted or modified.
 * - No API response shape is changed.
 * - PayrollService::confirmPayroll() uses lockForUpdate() which already
 *   prevents most duplicates; this adds DB-level guarantee.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payslips')) {
            return;
        }

        // Check for duplicate (payroll_run_id, staff_id) combinations
        $duplicates = DB::table('payslips')
            ->select('payroll_run_id', 'staff_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('payroll_run_id', 'staff_id')
            ->having('cnt', '>', 1)
            ->count();

        if ($duplicates > 0) {
            \Illuminate\Support\Facades\Log::warning(
                "[Migration] Skipped unique constraint on payslips(payroll_run_id, staff_id) — {$duplicates} duplicate combination(s) found. Clean them first."
            );
            return;
        }

        $indexName = 'payslips_payroll_run_id_staff_id_unique';
        $indexExists = collect(Schema::getIndexes('payslips'))
            ->pluck('name')
            ->contains($indexName);

        if (! $indexExists) {
            Schema::table('payslips', function (Blueprint $table) use ($indexName) {
                $table->unique(['payroll_run_id', 'staff_id'], $indexName);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payslips')) {
            return;
        }

        $indexName = 'payslips_payroll_run_id_staff_id_unique';
        $indexExists = collect(Schema::getIndexes('payslips'))
            ->pluck('name')
            ->contains($indexName);

        if ($indexExists) {
            Schema::table('payslips', function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        }
    }
};
