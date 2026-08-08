<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_types', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_types', 'is_daily_entry')) {
                $table->boolean('is_daily_entry')->default(false)->after('has_shifts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_types', function (Blueprint $table) {
            if (Schema::hasColumn('activity_types', 'is_daily_entry')) {
                $table->dropColumn('is_daily_entry');
            }
        });
    }
};
