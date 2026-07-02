<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('club_settings', function (Blueprint $table) {
            $table->boolean('allow_partial_payment')->default(true)->after('grace_period_days');
        });
    }

    public function down(): void {
        Schema::table('club_settings', function (Blueprint $table) {
            $table->dropColumn('allow_partial_payment');
        });
    }
};
