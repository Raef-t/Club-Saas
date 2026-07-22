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
        Schema::table('staff', function (Blueprint $table) {
            $columnsToDrop = array_filter(['contract_type', 'shift_type', 'other_tasks'], function ($column) {
                return Schema::hasColumn('staff', $column);
            });

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'contract_type')) {
                $table->string('contract_type', 50)->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('staff', 'shift_type')) {
                $table->string('shift_type', 50)->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('staff', 'other_tasks')) {
                $table->text('other_tasks')->nullable()->after('end_date');
            }
        });
    }
};
