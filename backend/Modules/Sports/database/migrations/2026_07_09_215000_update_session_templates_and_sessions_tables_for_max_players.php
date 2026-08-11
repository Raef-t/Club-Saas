<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_session_templates', function (Blueprint $table) {
            if (Schema::hasColumn('sport_session_templates', 'max_players')) {
                $table->dropColumn('max_players');
            }
            $table->unsignedBigInteger('facility_id')->nullable()->change();
        });

        Schema::table('sports_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sports_sessions', 'max_players')) {
                $table->dropColumn('max_players');
            }
            $table->unsignedBigInteger('facility_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sport_session_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('sport_session_templates', 'max_players')) {
                $table->integer('max_players')->nullable();
            }
            $table->unsignedBigInteger('facility_id')->nullable(false)->change();
        });

        Schema::table('sports_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sports_sessions', 'max_players')) {
                $table->integer('max_players')->nullable();
            }
            $table->unsignedBigInteger('facility_id')->nullable(false)->change();
        });
    }
};
