<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports_sessions', function (Blueprint $table) {
            $table->string('session_type', 50)->default('group_class')->after('id')
                  ->comment('group_class, personal_training, facility_booking');
        });
    }

    public function down(): void
    {
        Schema::table('sports_sessions', function (Blueprint $table) {
            $table->dropColumn('session_type');
        });
    }
};
