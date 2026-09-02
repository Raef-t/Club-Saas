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
        if (Schema::hasColumn('club_settings', 'allow_partial_payment')) {
            Schema::table('club_settings', function (Blueprint $table) {
                $table->dropColumn('allow_partial_payment');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('club_settings', 'allow_partial_payment')) {
            Schema::table('club_settings', function (Blueprint $table) {
                $table->boolean('allow_partial_payment')->default(true)->after('grace_period_days');
            });
        }
    }
};
