<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clean the table first, as keeping old data without required column is problematic
        \Illuminate\Support\Facades\DB::table('staff_shifts')->truncate();

        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->dropColumn(['day_of_week', 'start_time', 'end_time']);
            $table->unsignedBigInteger('branch_shift_id')->after('staff_id');

            $table->foreign('branch_shift_id')->references('id')->on('branch_shifts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->dropForeign(['branch_shift_id']);
            $table->dropColumn('branch_shift_id');

            $table->tinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
        });
    }
};
