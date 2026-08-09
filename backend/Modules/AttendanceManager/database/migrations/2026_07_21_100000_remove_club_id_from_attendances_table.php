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
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'club_id')) {
                // Drop foreign key if exists. If it was just an index or plain column, this drops it.
                // Assuming it might have an index, we drop column directly, laravel handles index dropping usually, or we can just drop column.
                $table->dropColumn('club_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'club_id')) {
                $table->unsignedBigInteger('club_id')->nullable();
            }
        });
    }
};
