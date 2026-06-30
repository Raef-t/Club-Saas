<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('club_settings', function (Blueprint $table) {
            $table->decimal('allowed_debt_limit', 12, 2)->default(0.00)->after('language');
            $table->integer('grace_period_days')->default(0)->after('allowed_debt_limit');
        });
    }

    public function down(): void {
        Schema::table('club_settings', function (Blueprint $table) {
            $table->dropColumn(['allowed_debt_limit', 'grace_period_days']);
        });
    }
};
