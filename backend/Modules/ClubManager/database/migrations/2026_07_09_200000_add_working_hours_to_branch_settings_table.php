<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->time('working_hours_start')->nullable()->after('branch_id');
            $table->time('working_hours_end')->nullable()->after('working_hours_start');
        });
    }

    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn(['working_hours_start', 'working_hours_end']);
        });
    }
};
