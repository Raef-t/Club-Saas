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
            if (!Schema::hasColumn('staff', 'start_time')) {
                $table->time('start_time')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('staff', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $columns = array_filter(['start_time', 'end_time'], fn($col) => Schema::hasColumn('staff', $col));
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
