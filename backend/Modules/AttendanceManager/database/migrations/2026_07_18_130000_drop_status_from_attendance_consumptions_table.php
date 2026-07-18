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
        Schema::table('attendance_consumptions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_consumptions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_consumptions', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_consumptions', 'status')) {
                $table->string('status', 50)->default('consumed');
            }
        });
    }
};
