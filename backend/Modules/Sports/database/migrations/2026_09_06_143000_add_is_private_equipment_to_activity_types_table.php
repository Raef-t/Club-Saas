<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_types', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_types', 'is_private_equipment')) {
                $table->boolean('is_private_equipment')->default(true)->after('is_daily_entry');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_types', function (Blueprint $table) {
            if (Schema::hasColumn('activity_types', 'is_private_equipment')) {
                $table->dropColumn('is_private_equipment');
            }
        });
    }
};
