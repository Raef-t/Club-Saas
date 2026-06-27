<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_commission_rules', function (Blueprint $table) {
            $table->integer('min_players')->nullable()->after('rate_value');
            $table->integer('max_players')->nullable()->after('min_players');
            $table->boolean('is_active')->default(true)->after('max_players');
        });
    }

    public function down(): void
    {
        Schema::table('staff_commission_rules', function (Blueprint $table) {
            $table->dropColumn(['min_players', 'max_players', 'is_active']);
        });
    }
};
