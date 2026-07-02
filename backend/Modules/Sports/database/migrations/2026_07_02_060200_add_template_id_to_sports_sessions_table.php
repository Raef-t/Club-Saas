<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->after('facility_id');
            $table->foreign('template_id')->references('id')->on('sport_session_templates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sports_sessions', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn('template_id');
        });
    }
};
