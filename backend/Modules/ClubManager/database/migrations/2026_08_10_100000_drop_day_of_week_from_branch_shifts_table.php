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
        Schema::table('branch_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('branch_shifts', 'day_of_week')) {
                $table->dropColumn('day_of_week');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_shifts', function (Blueprint $table) {
            $table->integer('day_of_week')->nullable()->comment('0=Sunday, 6=Saturday');
        });
    }
};
